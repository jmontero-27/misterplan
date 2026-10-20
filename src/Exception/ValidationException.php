<?php

declare(strict_types=1);

namespace App\Exception;

use InvalidArgumentException;

final class ValidationException extends InvalidArgumentException
{
    public function __construct(
        private readonly array $errors
    ) {
        parent::__construct('Validation failed');
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}