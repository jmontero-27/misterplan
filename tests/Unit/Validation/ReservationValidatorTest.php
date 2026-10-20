<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;

use App\Enum\ReservationStatus;
use App\Exception\ValidationException;
use App\Validation\ReservationValidator;

final class ReservationValidatorTest extends TestCase
{
    private ReservationValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ReservationValidator();
    }

    public function testValidFilters(): void
    {
        $filters = [
            'estado' => ReservationStatus::CONFIRMED->value,
            'huesped' => ' Juan ',
            'fecha_desde' => '2026-11-01',
            'fecha_hasta' => '2026-11-30',
        ];

        $expected = [
            'estado' => ReservationStatus::CONFIRMED->value,
            'huesped' => 'Juan',
            'fecha_desde' => '2026-11-01',
            'fecha_hasta' => '2026-11-30',
        ];

        $result = $this->validator->validateFilters($filters);

        ksort($expected);
        ksort($result);

        $this->assertSame($expected, $result);
    }

    public function testEmptyFilters(): void
    {
        $result = $this->validator->validateFilters([]);

        $this->assertSame([], $result);
    }

    public function testInvalidStatus(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator->validateFilters(
            [
                'estado' => 'inventado',
            ]
        );
    }

    public function testInvalidDate(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator->validateFilters(
            [
                'fecha_desde' => '2026-02-30',
            ]
        );
    }

    public function testInvalidDateRange(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator->validateFilters(
            [
                'fecha_desde' => '2026-12-31',
                'fecha_hasta' => '2026-01-01',
            ]
        );
    }

    public function testInvalidFilterType(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator->validateFilters(
            [
                'estado' => [
                    ReservationStatus::PENDING->value
                ],
            ]
        );
    }

    public function testUnknownFilter(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator->validateFilters(
            [
                'desconocido' => 'valor',
            ]
        );
    }

    public function testValidReservation(): void
    {
        $data = $this->validReservationData();

        $result = $this->validator->validateCreate($data);

        $this->assertSame($data, $result);
    }

    public function testMissingRequiredFields(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator->validateCreate([]);
    }

    public function testInvalidEmail(): void
    {
        $data = $this->validReservationData();
        $data['email_huesped'] = 'correo-invalido';

        $this->expectException(ValidationException::class);

        $this->validator->validateCreate($data);
    }

    public function testCheckoutBeforeCheckin(): void
    {
        $data = $this->validReservationData();
        $data['fecha_salida'] = '2026-11-01';

        $this->expectException(ValidationException::class);

        $this->validator->validateCreate($data);
    }

    public function testNegativeAmount(): void
    {
        $data = $this->validReservationData();
        $data['importe'] = '-100.00';

        $this->expectException(ValidationException::class);

        $this->validator->validateCreate($data);
    }

    public function testUnknownReservationField(): void
    {
        $data = $this->validReservationData();
        $data['estado'] = ReservationStatus::CONFIRMED->value;

        $this->expectException(ValidationException::class);

        $this->validator->validateCreate($data);
    }

    private function validReservationData(): array
    {
        return [
            'nombre_huesped' => 'Ana Sanchez',
            'email_huesped' => 'ana@example.com',
            'nombre_alojamiento' => 'Hotel Costa Azul',
            'fecha_entrada' => '2026-11-20',
            'fecha_salida' => '2026-11-25',
            'importe' => '450.00',
            'notas' => 'Habitacion con vistas',
        ];
    }
}