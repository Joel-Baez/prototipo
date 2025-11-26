<?php

use App\Controllers\FlightController;
use App\Controllers\NaveController;
use App\Controllers\ReservationController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../app/bootstrap.php';

$app = AppFactory::create();
$scriptName = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$app->setBasePath($scriptName === '' ? '/' : $scriptName);

// CORS debe aplicarse siempre, incluso cuando haya errores o middleware posteriores
$app->options('/{routes:.+}', fn (Request $req, Response $res) => $res);
$app->add(function (Request $request, $handler) {
    $response = $handler->handle($request);

    $response = $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true');

    if ($request->getMethod() === 'OPTIONS') {
        return $response->withStatus(200);
    }

    return $response;
});

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode(['service' => 'flights_ms', 'status' => 'ok']));
    return $response->withHeader('Content-Type', 'application/json');
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
