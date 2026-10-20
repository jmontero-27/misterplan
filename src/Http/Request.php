<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly array $queryParams = [],
        private readonly array $body = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        $body = [];

        $content = file_get_contents('php://input');

        if ($content !== false && $content !== '') {
            $decoded = json_decode($content, true);

            if (
                json_last_error() !== JSON_ERROR_NONE
                ||
                !is_array($decoded) || array_is_list($decoded)
            ) {
                throw new \InvalidArgumentException('Invalid JSON body');
            }

            $body = $decoded;
        }

        return new self(
            method: $method,
            uri: $uri,
            queryParams: $_GET,
            body: $body,
        );
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function getBody(): array
    {
        return $this->body;
    }
}