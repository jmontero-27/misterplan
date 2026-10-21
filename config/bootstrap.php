<?php

declare(strict_types=1);

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

use App\Controller\Api\ApiReservationController;
use App\Controller\Web\ReservationController;
use App\Http\Application;
use App\Http\ControllerResolver;
use App\Http\Router;
use App\Repository\ReservationEventRepository;
use App\Repository\ReservationRepository;
use App\Service\ReservationService;
use App\Twig\AppExtension;
use App\Validation\ReservationValidator;

// Database connection
$createConnection = require __DIR__ . '/database.php';
$pdo = $createConnection();

// Repositories
$reservationRepository = new ReservationRepository($pdo);
$reservationEventRepository = new ReservationEventRepository($pdo);

// Validators
$reservationValidator = new ReservationValidator();

// Services
$reservationService = new ReservationService(
    $reservationRepository,
    $reservationEventRepository,
    $reservationValidator,
    $pdo
);

// Template engine
$twig = new Environment(
    new FilesystemLoader(dirname(__DIR__) . '/templates'),
    [
        'cache' => false,
        'debug' => true,
        'autoescape' => 'html',
    ]
);

$twig->addExtension(new AppExtension());

// Controllers
$apiReservationController = new ApiReservationController($reservationService);
$reservationController = new ReservationController(
    $reservationService,
    $twig
);

// Controller resolver
$resolver = new ControllerResolver();

$resolver->register(
    ApiReservationController::class,
    $apiReservationController
);

$resolver->register(
    ReservationController::class,
    $reservationController
);

// Router and route registration
$router = new Router($resolver);

$registerRoutes = require __DIR__ . '/routes.php';
$registerRoutes($router);

return new Application($router, $twig);