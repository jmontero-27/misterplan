<?php

declare(strict_types=1);

use App\Http\Application;
use App\Http\JsonResponse;
use App\Http\Request;

require dirname(__DIR__) . '/vendor/autoload.php';

ini_set('display_errors', '0');
ini_set('log_errors', '1');

try {
    $router = require dirname(__DIR__) . '/config/bootstrap.php';

    $application = new Application($router);

    $request = Request::fromGlobals();

    $response = $application->handle($request);
} catch (\InvalidArgumentException $exception) {
    $response = new JsonResponse(
        ['error' => $exception->getMessage()],
        400
    );
} catch (\Throwable $exception) {
    error_log((string) $exception);

    $response = new JsonResponse(
        ['error' => 'Internal Server Error'],
        500
    );
}

$response->send();
