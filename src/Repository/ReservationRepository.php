<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class ReservationRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function findAll(array $filters = []): array
    {
        $sql = 'SELECT * FROM reserva WHERE 1 = 1';
        $params = [];

        if (
            isset($filters['estado'])
            &&
            $filters['estado'] !== ''
        ) {
            $sql .= ' AND estado = :estado';
            $params['estado'] = $filters['estado'];
        }

        if (
            isset($filters['fecha_desde'])
            &&
            $filters['fecha_desde'] !== ''
        ) {
            $sql .= ' AND fecha_entrada >= :fecha_desde';
            $params['fecha_desde'] = $filters['fecha_desde'];
        }

        if (
            isset($filters['fecha_hasta'])
            &&
            $filters['fecha_hasta'] !== ''
        ) {
            $sql .= ' AND fecha_entrada <= :fecha_hasta';
            $params['fecha_hasta'] = $filters['fecha_hasta'];
        }

        if (
            isset($filters['huesped'])
            &&
            $filters['huesped'] !== ''
        ) {
            $sql .= ' AND nombre_huesped LIKE :huesped';
            $params['huesped'] = '%' . $filters['huesped'] . '%';
        }

        $sql .= ' ORDER BY fecha_creacion DESC, id DESC';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM reserva WHERE id = :id'
        );

        $statement->execute(['id' => $id]);

        $reservation = $statement->fetch();

        return $reservation === false ? null : $reservation;
    }

    public function findByIdForUpdate(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT *
            FROM reserva
            WHERE id = :id
            FOR UPDATE'
        );

        $statement->execute([
            'id' => $id,
        ]);

        $reservation = $statement->fetch(PDO::FETCH_ASSOC);

        return $reservation === false ? null : $reservation;
    }

    public function insert(array $data): int
    {
        $sql = '
            INSERT INTO reserva (
                nombre_huesped,
                email_huesped,
                nombre_alojamiento,
                fecha_entrada,
                fecha_salida,
                estado,
                importe,
                notas
            ) VALUES (
                :nombre_huesped,
                :email_huesped,
                :nombre_alojamiento,
                :fecha_entrada,
                :fecha_salida,
                :estado,
                :importe,
                :notas
            )
        ';

        $statement = $this->pdo->prepare($sql);

        $statement->execute([
            'nombre_huesped' => $data['nombre_huesped'],
            'email_huesped' => $data['email_huesped'],
            'nombre_alojamiento' => $data['nombre_alojamiento'],
            'fecha_entrada' => $data['fecha_entrada'],
            'fecha_salida' => $data['fecha_salida'],
            'estado' => 'pendiente',
            'importe' => $data['importe'],
            'notas' => $data['notas'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE reserva
            SET estado = :estado
            WHERE id = :id'
        );

        $statement->execute([
            'estado' => $status,
            'id' => $id,
        ]);
    }
}