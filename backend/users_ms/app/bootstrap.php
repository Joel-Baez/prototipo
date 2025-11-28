<?php

function jsonError(string $message, int $status = 400): array
{
    return ['error' => $message, 'status' => $status];
}

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    http_response_code(500);
    echo json_encode(jsonError('Falta vendor/autoload.php. Corre los comandos de Composer indicados en el README antes de iniciar.'));
    exit;
}

require_once $autoloadPath;

require_once __DIR__ . '/Config/database.php';
