<?php

use App\Controllers\FlightController;
use App\Controllers\NaveController;
use App\Controllers\ReservationController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use Slim\Factory\AppFactory;

require __DIR__ . '/../app/bootstrap.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, false, false);

$app->get('/', function () {
    return json_encode(['service' => 'flights_ms', 'status' => 'ok']);
});

$auth = new AuthMiddleware();
$admin = new RoleMiddleware(['administrador']);
$gestor = new RoleMiddleware(['gestor', 'administrador']);

$app->group('/flights', function ($group) {
    $group->get('', [FlightController::class, 'index']);
    $group->get('/search', [FlightController::class, 'search']);
    $group->post('', [FlightController::class, 'store']);
    $group->put('/{id}', [FlightController::class, 'update']);
    $group->delete('/{id}', [FlightController::class, 'delete']);
})->add($admin)->add($auth);

$app->group('/naves', function ($group) {
    $group->get('', [NaveController::class, 'index']);
    $group->post('', [NaveController::class, 'store']);
    $group->put('/{id}', [NaveController::class, 'update']);
    $group->delete('/{id}', [NaveController::class, 'delete']);
})->add($admin)->add($auth);

$app->group('/reservations', function ($group) {
    $group->get('', [ReservationController::class, 'index']);
    $group->get('/user/{userId}', [ReservationController::class, 'byUser']);
    $group->post('', [ReservationController::class, 'store']);
    $group->delete('/{id}', [ReservationController::class, 'cancel']);
})->add($gestor)->add($auth);

$app->run();
