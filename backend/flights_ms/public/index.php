<?php

use App\Controllers\FlightController;
use App\Controllers\NaveController;
use App\Controllers\ReservationController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use Slim\Factory\AppFactory;

require __DIR__ . '/../app/bootstrap.php';

$app = AppFactory::create();

$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
if ($scriptDir && $scriptDir !== '/') {
    $app->setBasePath($scriptDir);
}

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

$app->options('/{routes:.+}', fn($request, $response) => $response);
$app->add(function ($request, $handler) {
    $origin = $request->getHeaderLine('Origin') ?: '*';
    $response = $handler->handle($request);
    $response = $response
        ->withHeader('Access-Control-Allow-Origin', $origin)
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withHeader('Content-Type', 'application/json');

    if ($request->getMethod() === 'OPTIONS') {
        return $response->withStatus(200);
    }

    return $response;
});

$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $response->getBody()->write(json_encode(['service' => 'flights_ms', 'status' => 'ok']));
    return $response->withHeader('Content-Type', 'application/json');
});

$authMiddleware = new AuthMiddleware();
$adminMiddleware = new RoleMiddleware(['administrador']);
$gestorMiddleware = new RoleMiddleware(['gestor']);

// Vuelos
$app->get('/flights', [new FlightController(), 'search'])->add($authMiddleware);
$app->post('/flights', [new FlightController(), 'store'])->add($adminMiddleware)->add($authMiddleware);
$app->put('/flights/{id}', [new FlightController(), 'update'])->add($adminMiddleware)->add($authMiddleware);
$app->delete('/flights/{id}', [new FlightController(), 'delete'])->add($adminMiddleware)->add($authMiddleware);

// Naves
$app->get('/naves', [new NaveController(), 'index'])->add($adminMiddleware)->add($authMiddleware);
$app->post('/naves', [new NaveController(), 'store'])->add($adminMiddleware)->add($authMiddleware);
$app->put('/naves/{id}', [new NaveController(), 'update'])->add($adminMiddleware)->add($authMiddleware);
$app->delete('/naves/{id}', [new NaveController(), 'delete'])->add($adminMiddleware)->add($authMiddleware);

// Reservas (gestor)
$app->get('/reservations', [new ReservationController(), 'index'])->add($gestorMiddleware)->add($authMiddleware);
$app->get('/reservations/by-user/{userId}', [new ReservationController(), 'byUser'])->add($gestorMiddleware)->add($authMiddleware);
$app->post('/reservations', [new ReservationController(), 'store'])->add($gestorMiddleware)->add($authMiddleware);
$app->put('/reservations/{id}/cancel', [new ReservationController(), 'cancel'])->add($gestorMiddleware)->add($authMiddleware);

$app->run();
