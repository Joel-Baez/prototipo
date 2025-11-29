<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Throwable;

$capsule = new Capsule();

try {
    $capsule->addConnection([
        'driver' => getenv('DB_CONNECTION') ?: 'mysql',
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'database' => getenv('DB_DATABASE') ?: 'vuelos_app',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'prefix' => '',
    ]);

    $capsule->setAsGlobal();
    $capsule->bootEloquent();
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(jsonError('No se pudo conectar a la base de datos. Verifica las credenciales en database.php o variables de entorno.', 500));
    exit;
}
