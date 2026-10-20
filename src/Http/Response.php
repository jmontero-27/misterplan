<?php

declare(strict_types=1);

namespace App\Http;

abstract class Response
{
    public function __construct(
        protected int $statusCode = 200,
        protected array $headers = []
    ) {
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    abstract public function send(): void;
}
