<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Enum\ReservationStatus;
use App\Repository\ReservationRepository;
use Tests\Integration\DatabaseTestCase;

final class ReservationRepositoryTest extends DatabaseTestCase
{
    private ReservationRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new ReservationRepository($this->pdo);
    }

    public function testInsertReservation(): void
    {
        $id = $this->repository->insert($this->validData());

        $this->assertGreaterThan(0, $id);

        $reservation = $this->repository->findById($id);

        $this->assertNotNull($reservation);

        $this->assertSame(
            'Test PHPUnit',
            $reservation['nombre_huesped']
        );

        $this->assertSame(
            ReservationStatus::PENDING->value,
            $reservation['estado']
        );

        $this->assertSame(
            '450.00',
            $reservation['importe']
        );
    }

    public function testFindById(): void
    {
        $id = $this->repository->insert($this->validData());

        $reservation = $this->repository->findById($id);

        $this->assertNotNull($reservation);

        $this->assertSame(
            $id,
            (int) $reservation['id']
        );
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $reservation = $this->repository->findById(PHP_INT_MAX);

        $this->assertNull($reservation);
    }

    public function testFindAll(): void
    {
        $this->repository->insert($this->validData());

        $secondReservation = $this->validData();
        $secondReservation['nombre_huesped'] = 'Maria Lopez';

        $this->repository->insert($secondReservation);

        $reservations = $this->repository->findAll();

        $this->assertCount(
            2,
            $reservations
        );
    }

    public function testFilterByStatus(): void
    {
        $id = $this->repository->insert($this->validData());

        $this->repository->updateStatus(
            $id,
            ReservationStatus::CONFIRMED->value
        );

        $this->repository->insert($this->validData());

        $reservations = $this->repository->findAll([
            'estado' => ReservationStatus::CONFIRMED->value,
        ]);

        $this->assertCount(1, $reservations);

        $this->assertSame(
            ReservationStatus::CONFIRMED->value,
            $reservations[0]['estado']
        );

        $this->assertSame(
            $id,
            (int) $reservations[0]['id']
        );
    }

    public function testFilterByGuest(): void
    {
        $this->repository->insert($this->validData());

        $secondReservation = $this->validData();
        $secondReservation['nombre_huesped'] = 'Maria Lopez';

        $this->repository->insert($secondReservation);

        $reservations = $this->repository->findAll([
            'huesped' => 'Maria',
        ]);

        $this->assertCount(1, $reservations);

        $this->assertSame(
            'Maria Lopez',
            $reservations[0]['nombre_huesped']
        );
    }

    public function testFilterByDates(): void
    {
        $this->repository->insert($this->validData());

        $secondReservation = $this->validData();
        $secondReservation['fecha_entrada'] = '2026-12-10';
        $secondReservation['fecha_salida'] = '2026-12-15';

        $this->repository->insert($secondReservation);

        $reservations = $this->repository->findAll([
            'fecha_desde' => '2026-12-01',
            'fecha_hasta' => '2026-12-31',
        ]);

        $this->assertCount(1, $reservations);

        $this->assertSame(
            '2026-12-10',
            $reservations[0]['fecha_entrada']
        );
    }

    public function testUpdateStatus(): void
    {
        $id = $this->repository->insert($this->validData());

        $this->repository->updateStatus(
            $id,
            ReservationStatus::CANCELLED->value
        );

        $reservation = $this->repository->findById($id);

        $this->assertNotNull($reservation);

        $this->assertSame(
            ReservationStatus::CANCELLED->value,
            $reservation['estado']
        );
    }

    public function testFindByIdForUpdate(): void
    {
        $id = $this->repository->insert($this->validData());

        $this->pdo->beginTransaction();

        try {
            $reservation = $this->repository->findByIdForUpdate($id);

            $this->assertNotNull($reservation);

            $this->assertEquals(
                $id,
                $reservation['id']
            );
        } finally {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
        }
    }

    private function validData(): array
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
}
