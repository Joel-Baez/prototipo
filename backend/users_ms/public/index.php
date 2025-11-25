<?php

use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use Slim\Factory\AppFactory;

require __DIR__ . '/../app/bootstrap.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, false, false);

// CORS global
$app->options('/{routes:.+}', fn ($req, $res) => $res);
$app->add(function ($request, $handler) {
    $origin = $request->getHeaderLine('Origin') ?: '*';
    $response = $handler->handle($request);
    $response = $response
        ->withHeader('Access-Control-Allow-Origin', $origin)
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true');

    if ($request->getMethod() === 'OPTIONS') {
        return $response->withStatus(200);
    }

    return $response;
});

$app->get('/', function () {
    return json_encode(['service' => 'users_ms', 'status' => 'ok']);
});

$auth = new AuthMiddleware();

$app->post('/login', [AuthController::class, 'login']);

$app->group('/users', function ($group) {
    $group->post('', [UserController::class, 'register']);
    $group->get('', [UserController::class, 'index']);
    $group->put('/{id}', [UserController::class, 'update']);
    $group->put('/{id}/role', [UserController::class, 'changeRole']);
})->add(new RoleMiddleware(['administrador']))->add($auth);

$app->post('/logout', [AuthController::class, 'logout'])->add($auth);
$app->get('/me', [AuthController::class, 'me'])->add($auth);

$app->run();
