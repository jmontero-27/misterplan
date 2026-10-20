<?php

declare(strict_types=1);

namespace Tests\Functional;

use App\Controller\Api\ApiReservationController;
use App\Http\ControllerResolver;
use App\Http\Application;
use App\Http\Router;
use App\Repository\ReservationEventRepository;
use App\Repository\ReservationRepository;
use App\Service\ReservationService;
use App\Validation\ReservationValidator;
use Tests\Integration\DatabaseTestCase;

abstract class FunctionalTestCase extends DatabaseTestCase
{
    protected TestClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $service = new ReservationService(
            new ReservationRepository($this->pdo),
            new ReservationEventRepository($this->pdo),
            new ReservationValidator(),
            $this->pdo
        );

        $resolver = new ControllerResolver();

        $resolver->register(
            ApiReservationController::class,
            new ApiReservationController($service)
        );

        $router = new Router($resolver);

        $registerRoutes = require dirname(__DIR__, 2) . '/config/routes.php';

        $registerRoutes($router);

        $this->client = new TestClient(
            new Application($router)
        );
    }
}
