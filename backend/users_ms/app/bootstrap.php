<?php

use Dotenv\Dotenv;

function jsonError(string $message, int $status = 400): array
{
    return ['error' => $message, 'status' => $status];
}

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    http_response_code(500);
    echo json_encode(jsonError('Falta vendor/autoload.php. Ejecuta "composer install" dentro de backend/users_ms".'));
    exit;
}

require_once $autoloadPath;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

require_once __DIR__ . '/Config/database.php';
