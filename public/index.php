<?php

declare(strict_types=1);

use App\Http\JsonResponse;
use App\Http\Request;

require dirname(__DIR__) . '/vendor/autoload.php';

ini_set('display_errors', '0');
ini_set('log_errors', '1');

try {
    $dotenv = Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__));
    $dotenv->safeLoad();

    $application = require dirname(__DIR__) . '/config/bootstrap.php';

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