<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use PDOException;

use App\Enum\ReservationEventType;
use App\Repository\ReservationEventRepository;
use App\Repository\ReservationRepository;
use Tests\Integration\DatabaseTestCase;

final class ReservationEventRepositoryTest extends DatabaseTestCase
{
    private ReservationEventRepository $repository;
    private ReservationRepository $reservationRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new ReservationEventRepository($this->pdo);
        $this->reservationRepository = new ReservationRepository($this->pdo);
    }

    public function testInsertEvent(): void
    {
        $reservationId = $this->createReservation();

        $eventType = ReservationEventType::CREATION;

        $eventId = $this->repository->insert(
            $reservationId,
            $eventType->value,
            $eventType->description()
        );

        $this->assertGreaterThan(0, $eventId);

        $events = $this->repository->findByReservationId($reservationId);

        $this->assertCount(1, $events);

        $this->assertSame(
            $eventId,
            (int) $events[0]['id']
        );

        $this->assertSame(
            $reservationId,
            (int) $events[0]['reserva_id']
        );

        $this->assertSame(
            $eventType->value,
            $events[0]['tipo']
        );

        $this->assertSame(
            $eventType->description(),
            $events[0]['descripcion']
        );
    }

    public function testFindByReservationIdReturnsEmptyArray(): void
    {
        $events = $this->repository->findByReservationId(PHP_INT_MAX);

        $this->assertSame([], $events);
    }

    public function testEventsBelongToCorrectReservation(): void
    {
        $firstReservationId = $this->createReservation();
        $secondReservationId = $this->createReservation();

        $eventType = ReservationEventType::CREATION;

        $this->repository->insert(
            $firstReservationId,
            $eventType->value,
            'First reservation created'
        );

        $this->repository->insert(
            $secondReservationId,
            $eventType->value,
            'Second reservation created'
        );

        $events = $this->repository->findByReservationId(
            $firstReservationId
        );

        $this->assertCount(1, $events);

        $this->assertSame(
            $firstReservationId,
            (int) $events[0]['reserva_id']
        );

        $this->assertSame(
            'First reservation created',
            $events[0]['descripcion']
        );
    }

    public function testEventsAreOrderedChronologically(): void
    {
        $reservationId = $this->createReservation();

        $this->repository->insert(
            $reservationId,
            ReservationEventType::CREATION->value,
            'First event'
        );

        $this->repository->insert(
            $reservationId,
            ReservationEventType::CONFIRMATION->value,
            'Second event'
        );

        $this->repository->insert(
            $reservationId,
            ReservationEventType::CANCELLATION->value,
            'Third event'
        );

        $events = $this->repository->findByReservationId($reservationId);

        $this->assertCount(3, $events);

        $this->assertSame(
            [
                'First event',
                'Second event',
                'Third event',
            ],
            array_column($events, 'descripcion')
        );

        $this->assertSame(
            [
                ReservationEventType::CREATION->value,
                ReservationEventType::CONFIRMATION->value,
                ReservationEventType::CANCELLATION->value,
            ],
            array_column($events, 'tipo')
        );
    }

    public function testCannotInsertEventForNonexistentReservation(): void
    {
        $this->expectException(PDOException::class);

        $this->repository->insert(
            PHP_INT_MAX,
            ReservationEventType::CREATION->value,
            'Event without reservation'
        );
    }

    private function createReservation(): int
    {
        return $this->reservationRepository->insert([
            'nombre_huesped' => 'Test PHPUnit',
            'email_huesped' => 'test@example.com',
            'nombre_alojamiento' => 'Hotel Test',
            'fecha_entrada' => '2026-11-20',
            'fecha_salida' => '2026-11-25',
            'importe' => '450.00',
            'notas' => null,
        ]);
    }
}
