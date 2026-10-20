<?php

declare(strict_types=1);

namespace App\Twig;

use DateTimeImmutable;
use DateTimeZone;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'fecha_local',
                [
                    $this,
                    'formatDate'
                ]
            ),
        ];
    }

    public function formatDate(
        string $value,
        string $format = 'd/m/Y H:i:s'
    ): string {
        $date = new DateTimeImmutable(
            $value,
            new DateTimeZone('UTC')
        );

        return $date
            ->setTimezone(new DateTimeZone('Europe/Madrid'))
            ->format($format)
        ;
    }
}
