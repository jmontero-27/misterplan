<?php

declare(strict_types=1);

namespace App\Controller\Web;

use Twig\Environment;

use App\Exception\NotFoundException;
use App\Http\HtmlResponse;
use App\Http\Request;
use App\Service\ReservationService;

final class ReservationController
{
    public function __construct(
        private readonly ReservationService $reservationService,
        private readonly Environment $twig,
    ) {
    }

    public function list(): HtmlResponse
    {
        return new HtmlResponse(
            $this->twig->render(
                'reservations/index.html.twig',
                [
                    'title' => 'Gestión de reservas',
                    'reservations' => $this->reservationService->findAll(),
                ]
            )
        );
    }

    public function read(Request $request, int $id): HtmlResponse
    {
        $reservation = $this->reservationService->findById($id);

        if ($reservation === null) {
            throw new NotFoundException('Reservation not found');
        }

        return new HtmlResponse(
            $this->twig->render(
                'reservations/detail.html.twig',
                [
                    'reserva' => $reservation,
                ]
            )
        );
    }
}
