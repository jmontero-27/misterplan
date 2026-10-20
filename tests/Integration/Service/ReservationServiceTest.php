<?php

declare(strict_types=1);

namespace Tests\Integration\Service;

use PDO;

use App\Enum\ReservationEventType;
use App\Enum\ReservationStatus;
use App\Exception\ConflictException;
use App\Exception\NotFoundException;
use App\Repository\ReservationEventRepository;
use App\Repository\ReservationRepository;
use App\Service\ReservationService;
use App\Validation\ReservationValidator;
use Tests\Integration\DatabaseTestCase;

final class ReservationServiceTest extends DatabaseTestCase
{
    private ReservationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ReservationService(
            new ReservationRepository($this->pdo),
            new ReservationEventRepository($this->pdo),
            new ReservationValidator(),
            $this->pdo
        );
    }

    private function validReservationData(): array
    {
        return [
            'nombre_huesped' => 'Test PHPUnit',
            'email_huesped' => 'test@example.com',
            'nombre_alojamiento' => 'Hotel Test',
            'fecha_entrada' => '2026-11-20',
            'fecha_salida' => '2026-11-25',
            'importe' => '450.00',
            'notas' => 'Reserva de prueba',
        ];
    }

    public function testCreateReservation(): void
    {
        $reservation = $this->service->create(
            $this->validReservationData()
        );

        $this->assertSame(
            ReservationStatus::PENDING->value,
            $reservation['estado']
        );

        $this->assertCount(
            1,
            $reservation['eventos']
        );

        $this->assertSame(
            ReservationEventType::CREATION->value,
            $reservation['eventos'][0]['tipo']
        );
    }

    public function testConfirmPendingReservation(): void
    {
        $reservation = $this->service->create(
            $this->validReservationData()
        );

        $updatedReservation = $this->service->confirm(
            (int) $reservation['id']
        );

        $this->assertSame(
            ReservationStatus::CONFIRMED->value,
            $updatedReservation['estado']
        );

        $this->assertCount(
            2,
            $updatedReservation['eventos']
        );

        $this->assertSame(
            ReservationEventType::CONFIRMATION->value,
            $updatedReservation['eventos'][1]['tipo']
        );
    }

    public function testCancelConfirmedReservation(): void
    {
        $reservation = $this->service->create(
            $this->validReservationData()
        );

        $id = (int) $reservation['id'];

        $this->service->confirm($id);

        $updatedReservation = $this->service->cancel($id);

        $this->assertSame(
            ReservationStatus::CANCELLED->value,
            $updatedReservation['estado']
        );

        $this->assertCount(
            3,
            $updatedReservation['eventos']
        );

        $this->assertSame(
            ReservationEventType::CANCELLATION->value,
            $updatedReservation['eventos'][2]['tipo']
        );
    }

    public function testCannotConfirmCancelledReservation(): void
    {
        $reservation = $this->service->create(
            $this->validReservationData()
        );

        $id = (int) $reservation['id'];

        $this->service->cancel($id);

        $this->expectException(ConflictException::class);

        $this->service->confirm($id);
    }

    public function testCannotCancelAlreadyCancelledReservation(): void
    {
        $reservation = $this->service->create(
            $this->validReservationData()
        );

        $id = (int) $reservation['id'];

        $this->service->cancel($id);

        $this->expectException(ConflictException::class);

        $this->service->cancel($id);
    }

    public function testReservationNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->confirm(PHP_INT_MAX);
    }
    
    public function testRollbackWhenEventInsertionFails(): void
    {
        $reservation = $this->service->create(
            $this->validReservationData()
        );

        $id = (int) $reservation['id'];

        $eventType = ReservationEventType::CONFIRMATION->value;

        $this->pdo->exec(
            "CREATE TRIGGER test_fail_confirmation_event
             BEFORE INSERT ON reserva_evento
             FOR EACH ROW
             BEGIN
                 IF NEW.tipo = " . $this->pdo->quote($eventType) . " THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Simulated event insertion failure';
                 END IF;
             END"
        );

        $exception = null;

        try {
            $this->service->confirm($id);
        } catch (\PDOException $e) {
            $exception = $e;
        } finally {
            $this->pdo->exec(
                'DROP TRIGGER IF EXISTS test_fail_confirmation_event'
            );
        }

        $this->assertInstanceOf(
            \PDOException::class,
            $exception
        );

        $this->assertFalse($this->pdo->inTransaction());

        $stmt = $this->pdo->prepare(
            'SELECT estado FROM reserva WHERE id = ?'
        );
        $stmt->execute([$id]);

        $this->assertSame(
            ReservationStatus::PENDING->value,
            $stmt->fetchColumn()
        );

        $stmt = $this->pdo->prepare(
            'SELECT tipo FROM reserva_evento
            WHERE reserva_id = ?
            ORDER BY id'
        );

        $stmt->execute([$id]);

        $this->assertSame(
            [ReservationEventType::CREATION->value],
            $stmt->fetchAll(PDO::FETCH_COLUMN)
        );
    }

    public function testConcurrentConnectionCannotModifyLockedReservation(): void
    {
        $reservation = $this->service->create(
            $this->validReservationData()
        );

        $id = (int) $reservation['id'];

        $this->pdo->beginTransaction();

        try {
            $lockedReservation = (new ReservationRepository($this->pdo))->findByIdForUpdate($id);

            $this->assertNotNull($lockedReservation);

            $this->assertSame(
                ReservationStatus::PENDING->value,
                $lockedReservation['estado']
            );

            $worker = <<<'PHP'
            $pdo = new PDO(
                sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    getenv('DB_HOST') ?: 'db',
                    getenv('DB_PORT') ?: '3306',
                    getenv('DB_NAME')
                ),
                'root',
                getenv('DB_ROOT_PASSWORD') ?: '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]
            );

            $pdo->exec('SET SESSION innodb_lock_wait_timeout = 1');

            try {
                $pdo->beginTransaction();

                $statement = $pdo->prepare(
                    'SELECT estado FROM reserva WHERE id = ? FOR UPDATE'
                );

                $statement->execute([(int) $argv[1]]);
                $statement->fetchColumn();

                $pdo->commit();

                echo 'LOCK_ACQUIRED';
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                echo ($e->errorInfo[1] ?? null) === 1205 ? 'LOCK_TIMEOUT' : 'UNEXPECTED_SQL_ERROR';
            }
            PHP;

            $process = proc_open(
                [PHP_BINARY, '-r', $worker, (string) $id],
                [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ],
                $pipes
            );

            if (!is_resource($process)) {
                $this->fail('Unable to start concurrent process');
            }

            fclose($pipes[0]);

            $output = trim(stream_get_contents($pipes[1]));
            $errors = trim(stream_get_contents($pipes[2]));

            fclose($pipes[1]);
            fclose($pipes[2]);

            $exitCode = proc_close($process);

            $this->assertSame(
                0,
                $exitCode,
                $errors
            );

            $this->assertSame('LOCK_TIMEOUT', $output);
        } finally {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
        }

        $reservation = $this->service->findById($id);

        $this->assertSame(
            ReservationStatus::PENDING->value,
            $reservation['estado']
        );
        
        $this->assertCount(
            1,
            $reservation['eventos']
        );

        $confirmedReservation  = $this->service->confirm($id);

        $this->assertSame(
            ReservationStatus::CONFIRMED->value,
            $confirmedReservation ['estado']
        );
        
        $this->assertCount(
            2,
            $confirmedReservation ['eventos']
        );
    }
}