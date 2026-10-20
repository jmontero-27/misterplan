<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class ReservationEventRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function findByReservationId(int $reservationId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                id,
                reserva_id,
                tipo,
                descripcion,
                fecha_creacion
             FROM reserva_evento
             WHERE reserva_id = :reserva_id
             ORDER BY fecha_creacion ASC, id ASC'
        );

        $statement->execute([
            'reserva_id' => $reservationId,
        ]);

        return $statement->fetchAll();
    }

    public function insert(
        int $reservationId,
        string $type,
        string $description
    ): int {
        $statement = $this->pdo->prepare(
            'INSERT INTO reserva_evento (
                reserva_id,
                tipo,
                descripcion
            ) VALUES (
                :reserva_id,
                :tipo,
                :descripcion
            )'
        );

        $statement->execute([
            'reserva_id' => $reservationId,
            'tipo' => $type,
            'descripcion' => $description,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}