<?php

declare(strict_types=1);

namespace App\Service;

use PDO;
use Throwable;

use App\Enum\ReservationStatus;
use App\Enum\ReservationEventType;
use App\Exception\ConflictException;
use App\Exception\NotFoundException;
use App\Repository\ReservationRepository;
use App\Repository\ReservationEventRepository;
use App\Validation\ReservationValidator;

final class ReservationService
{
    public function __construct(
        private readonly ReservationRepository $repository,
        private readonly ReservationEventRepository $eventRepository,
        private readonly ReservationValidator $validator,
        private readonly PDO $pdo
    ) {
    }

    public function create(array $data): array
    {
        $validated = $this->validator->validateCreate($data);

        $this->pdo->beginTransaction();

        try {
            $reservationId = $this->repository->insert($validated);

            $event = ReservationEventType::CREATION;

            $this->eventRepository->insert(
                $reservationId,
                $event->value,
                $event->description()
            );

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }

        $reservation = $this->findById($reservationId);

        if ($reservation === null) {
            throw new \RuntimeException('The reservation that was created could not be retrieved');
        }

        return $reservation;
    }

    public function findAll(array $filters = []): array
    {
        $validatedFilters = $this->validator->validateFilters($filters);

        return $this->repository->findAll($validatedFilters);
    }

    public function findById(int $id): ?array
    {
        $reservation = $this->repository->findById($id);

        if ($reservation === null) {
            return null;
        }

        $reservation['eventos'] = $this->eventRepository->findByReservationId($id);

        return $reservation;
    }

    public function confirm(int $id): array
    {
        return $this->changeStatus($id, ReservationStatus::CONFIRMED);
    }

    public function cancel(int $id): array
    {
        return $this->changeStatus($id, ReservationStatus::CANCELLED);
    }

    private function changeStatus(int $id, ReservationStatus $newStatus): array
    {
        $this->pdo->beginTransaction();

        try {
            $reservation = $this->repository->findByIdForUpdate($id);

            if ($reservation === null) {
                throw new NotFoundException('Reservation not found');
            }

            $status = ReservationStatus::from($reservation['estado']);

            if (!$status->canTransitionTo($newStatus)) {
                throw new ConflictException(
                    sprintf(
                        'You cannot change a reservation from %s to %s',
                        $status->value,
                        $newStatus->value
                    )
                );
            }

            $this->repository->updateStatus($id, $newStatus->value);

            $event = match ($newStatus) {
                ReservationStatus::CONFIRMED => ReservationEventType::CONFIRMATION,
                ReservationStatus::CANCELLED => ReservationEventType::CANCELLATION,
                default => throw new \LogicException('State transition not supported'),
            };

            $this->eventRepository->insert(
                $id,
                $event->value,
                $event->description()
            );

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }

        $reservationUpdated = $this->findById($id);

        if ($reservationUpdated === null) {
            throw new \RuntimeException('The updated reservation could not be retrieved');
        }

        return $reservationUpdated;
    }
}