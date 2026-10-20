<?php

declare(strict_types=1);

namespace App\Http;

final class JsonResponse extends Response
{
    public function __construct(
        private readonly array $data,
        int $statusCode = 200,
        array $headers = []
    ) {
        parent::__construct($statusCode, $headers);
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        header('Content-Type: application/json; charset=utf-8');

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo json_encode(
            $this->data,
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    }
}
