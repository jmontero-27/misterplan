<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$database = getenv('DB_NAME');

if ($database === false || $database === '') {
    throw new RuntimeException('DB_NAME must be configured to run tests');
}

if (!str_ends_with($database, '_test')) {
    putenv('DB_NAME=' . $database . '_test');
}