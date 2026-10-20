<?php

declare(strict_types=1);

namespace Tests\Integration;

use PDO;
use RuntimeException;

use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    protected PDO $pdo;
    private string $testDatabaseName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDatabaseName = getenv('DB_NAME');

        if (
            $this->testDatabaseName === false
            ||
            !str_ends_with($this->testDatabaseName, '_test')
        ) {
            throw new RuntimeException(
                'Invalid test database configuration'
            );
        }

        $this->pdo = new PDO(
            sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                getenv('DB_HOST') ?: 'db',
                getenv('DB_PORT') ?: '3306',
                $this->testDatabaseName
            ),
            'root',
            getenv('DB_ROOT_PASSWORD'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        $this->assertTestDatabase();
        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        try {
            if (isset($this->pdo)) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }

                $this->cleanDatabase();
            }
        } finally {
            parent::tearDown();
        }
    }

    protected function cleanDatabase(): void
    {
        $this->assertTestDatabase();

        $this->pdo->exec('DELETE FROM reserva_evento');
        $this->pdo->exec('DELETE FROM reserva');
    }

    private function assertTestDatabase(): void
    {
        $database = $this->pdo->query('SELECT DATABASE()')->fetchColumn();

        if ($database !== $this->testDatabaseName) {
            throw new RuntimeException('Operation blocked: invalid test database');
        }
    }
}
