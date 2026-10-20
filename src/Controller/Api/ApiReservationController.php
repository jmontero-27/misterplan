<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Service\ReservationService;

final class ApiReservationController
{
    public function __construct(
        private readonly ReservationService $service
    ) {
    }

    public function list(Request $request): JsonResponse
    {
        return new JsonResponse(
            [
                'data' => $this->service->findAll(
                    $request->getQueryParams()
                ),
            ]
        );
    }

    public function create(Request $request): JsonResponse
    {
        $reservation = $this->service->create(
            $request->getBody()
        );

        return new JsonResponse(
            [
                'data' => $reservation,
            ],
            201
        );
    }

    public function read(Request $request, string $id): JsonResponse
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            return new JsonResponse(
                [
                    'error' => 'Invalid Reservation ID',
                ],
                400
            );
        }

        $reservation = $this->service->findById((int) $id);

        if ($reservation === null) {
            return new JsonResponse(
                [
                    'error' => 'Reservation not found',
                ],
                404
            );
        }

        return new JsonResponse(
            [
                'data' => $reservation,
            ]
        );
    }

    public function confirm(Request $request, string $id): JsonResponse
    {
        $reservationId = $this->validateId($id);

        return new JsonResponse(
            [
                'data' => $this->service->confirm($reservationId),
            ]
        );
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $reservationId = $this->validateId($id);

        return new JsonResponse(
            [
                'data' => $this->service->cancel($reservationId),
            ]
        );
    }

    private function validateId(string $id): int
    {
        $validatedId = filter_var(
            $id,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ]
        );

        if ($validatedId === false) {
            throw new \InvalidArgumentException('Invalid Reservation ID');
        }

        return $validatedId;
    }
}