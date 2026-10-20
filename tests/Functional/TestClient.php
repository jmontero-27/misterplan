<?php

declare(strict_types=1);

namespace Tests\Functional;

use App\Http\Application;
use App\Http\Request;
use App\Http\Response;

final class TestClient
{
    public function __construct(
        private readonly Application $application
    ) {
    }

    public function request(
        string $method,
        string $uri,
        array $queryParams = [],
        array $body = []
    ): Response
    {
        $request = new Request(
            method: strtoupper($method),
            uri: $uri,
            queryParams: $queryParams,
            body: $body
        );

        return $this->application->handle($request);
    }
}
