<?php

declare(strict_types=1);

namespace App\Enum;

enum ReservationStatus: string
{
    case PENDING = 'pendiente';
    case CONFIRMED = 'confirmada';
    case CANCELLED = 'cancelada';

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::PENDING => in_array(
                $newStatus,
                [self::CONFIRMED, self::CANCELLED],
                true
            ),
            self::CONFIRMED => $newStatus === self::CANCELLED,
            self::CANCELLED => false,
        };
    }
}