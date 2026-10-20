<?php

declare(strict_types=1);

use App\Controller\Api\ApiReservationController;
use App\Controller\Web\ReservationController;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Http\Router;

return static function (Router $router): void {
    $router->add(
        'GET',
        '/api/health',
        static fn (Request $request): JsonResponse => new JsonResponse(['status' => 'ok'])
    );

    $router->add(
        'GET',
        '/api/reservations',
        [ApiReservationController::class, 'list']
    );

    $router->add(
        'GET',
        '/api/reservations/{id}',
        [ApiReservationController::class, 'read']
    );

    $router->add(
        'POST',
        '/api/reservations',
        [ApiReservationController::class, 'create']
    );

    $router->add(
        'PATCH',
        '/api/reservations/{id}/confirm',
        [ApiReservationController::class, 'confirm']
    );

    $router->add(
        'PATCH',
        '/api/reservations/{id}/cancel',
        [ApiReservationController::class, 'cancel']
    );

    $router->add(
        'GET',
        '/',
        [ReservationController::class, 'list']
    );

    $router->add(
        'GET',
        '/reservas/{id}',
        [ReservationController::class, 'read']
    );
};