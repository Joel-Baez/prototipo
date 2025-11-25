<?php

use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

require_once __DIR__ . '/Config/database.php';

function jsonError(string $message, int $status = 400): array
{
    return ['error' => $message, 'status' => $status];
}
