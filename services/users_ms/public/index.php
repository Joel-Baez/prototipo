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
