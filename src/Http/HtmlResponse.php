<?php

declare(strict_types=1);

namespace App\Http;

final class HtmlResponse extends Response
{
    public function __construct(
        private readonly string $content,
        int $statusCode = 200,
        array $headers = []
    ) {
        parent::__construct($statusCode, $headers);
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        header('Content-Type: text/html; charset=utf-8');

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->content;
    }
}
