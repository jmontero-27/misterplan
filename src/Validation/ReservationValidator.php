<?php

declare(strict_types=1);

namespace App\Validation;

use DateTimeImmutable;

use App\Enum\ReservationStatus;
use App\Exception\ValidationException;

final class ReservationValidator
{
    public function validateFilters(array $filters): array
    {
        $errors = [];
        $validated = [];

        $allowed = [
            'estado',
            'fecha_desde',
            'fecha_hasta',
            'huesped',
        ];

        foreach ($filters as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                $errors[$key] = 'Filter not allowed';
                continue;
            }

            if (!is_string($value)) {
                $errors[$key] = 'The value must be a string';
            }
        }

        if (isset($filters['estado']) && is_string($filters['estado'])) {
            $status = trim($filters['estado']);

            if ($status !== '') {
                if (ReservationStatus::tryFrom($status) === null) {
                    $errors['estado'] = 'Invalid status';
                } else {
                    $validated['estado'] = $status;
                }
            }
        }

        foreach (['fecha_desde', 'fecha_hasta'] as $field) {
            if (!isset($filters[$field]) || !is_string($filters[$field])) {
                continue;
            }

            $value = trim($filters[$field]);

            if ($value === '') {
                continue;
            }

            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

            if ($date === false || $date->format('Y-m-d') !== $value) {
                $errors[$field] = 'The date must be in the YYYY-MM-DD format';
            } else {
                $validated[$field] = $value;
            }
        }

        if (
            isset($validated['fecha_desde'], $validated['fecha_hasta'])
            &&
            $validated['fecha_desde'] > $validated['fecha_hasta']
        ) {
            $errors['fecha_hasta'] = 'The end date cannot be earlier than the start date';
        }

        if (isset($filters['huesped']) && is_string($filters['huesped'])) {
            $guest = trim($filters['huesped']);

            if ($guest !== '') {
                if (mb_strlen($guest) > 150) {
                    $errors['huesped'] = 'Maximum 150 characters';
                } else {
                    $validated['huesped'] = $guest;
                }
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $validated;
    }

    public function validateCreate(array $data): array
    {
        $errors = [];
        $validated = [];

        $requiredFields = [
            'nombre_huesped' => 150,
            'email_huesped' => 255,
            'nombre_alojamiento' => 200,
        ];

        foreach ($requiredFields as $field => $maxLength) {
            $value = $data[$field] ?? null;

            if (!is_string($value) || trim($value) === '') {
                $errors[$field] = 'This field is required';
                continue;
            }

            $value = trim($value);

            if (mb_strlen($value) > $maxLength) {
                $errors[$field] = "Maximum $maxLength characters";
                continue;
            }

            $validated[$field] = $value;
        }

        if (
            isset($validated['email_huesped'])
            &&
            filter_var($validated['email_huesped'], FILTER_VALIDATE_EMAIL) === false
        ) {
            $errors['email_huesped'] = 'The email address is invalid';
        }

        foreach (['fecha_entrada', 'fecha_salida'] as $field) {
            $value = $data[$field] ?? null;

            if (!is_string($value)) {
                $errors[$field] = 'The date is required';
                continue;
            }

            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

            if ($date === false || $date->format('Y-m-d') !== $value) {
                $errors[$field] = 'Invalid date format (YYYY-MM-DD)';
                continue;
            }

            $validated[$field] = $value;
        }

        if (
            isset($validated['fecha_entrada'], $validated['fecha_salida'])
            &&
            $validated['fecha_salida'] <= $validated['fecha_entrada']
        ) {
            $errors['fecha_salida'] = 'It must be after the entry date';
        }

        $amount = $data['importe'] ?? null;

        if (
            (!is_string($amount) && !is_int($amount))
            ||
            !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', (string) $amount)
        ) {
            $errors['importe'] = 'Invalid amount (maximum of 8 whole numbers and 2 decimal places)';
        } else {
            $validated['importe'] = (string) $amount;
        }

        $notes = $data['notas'] ?? null;

        if ($notes !== null && !is_string($notes)) {
            $errors['notas'] = 'Notes must be text or null';
        } elseif (is_string($notes) && mb_strlen($notes) > 5000) {
            $errors['notas'] = 'Maximum 5,000 characters';
        } else {
            $validated['notas'] = $notes;
        }

        $allowedFields = [
            ...array_keys($requiredFields),
            'fecha_entrada',
            'fecha_salida',
            'importe',
            'notas',
        ];

        foreach (array_keys($data) as $field) {
            if (!in_array($field, $allowedFields, true)) {
                $errors[$field] = 'Field not allowed';
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $validated;
    }
}