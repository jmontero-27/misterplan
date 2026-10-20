<?php

declare(strict_types=1);

namespace App\Enum;

enum ReservationEventType: string
{
    case CREATION = 'creacion';
    case CONFIRMATION = 'confirmacion';
    case CANCELLATION = 'cancelacion';

    public function description(): string
    {
        return match ($this) {
            self::CREATION => 'Reserva creada en estado pendiente',
            self::CONFIRMATION => 'Reserva confirmada',
            self::CANCELLATION => 'Reserva cancelada',
        };
    }
}