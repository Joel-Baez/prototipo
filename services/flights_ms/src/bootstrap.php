<?php

use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv->load();
} else {
    $dotenv->safeLoad();
}

$capsule = new Capsule();
$capsule->addConnection([
    'driver' => $_ENV['DB_CONNECTION'] ?? 'mysql',
    'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'port' => $_ENV['DB_PORT'] ?? 3306,
    'database' => $_ENV['DB_DATABASE'] ?? 'vuelos_app',
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

function jsonError(string $message, int $status = 400): array
{
    return ['error' => $message, 'status' => $status];
}
