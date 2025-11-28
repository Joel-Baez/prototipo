<?php

use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response;

require __DIR__ . '/../app/bootstrap.php';

$app = AppFactory::create();

// Ajustar base path cuando se sirve desde subcarpeta o php -S
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
if ($scriptDir && $scriptDir !== '/') {
    $app->setBasePath($scriptDir);
}

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

// CORS
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

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $response->getBody()->write(json_encode(['service' => 'users_ms', 'status' => 'ok']));
    return $response->withHeader('Content-Type', 'application/json');
});

$authMiddleware = new AuthMiddleware();
$adminMiddleware = new RoleMiddleware(['administrador']);

$app->post('/login', [new AuthController(), 'login']);
$app->post('/logout', [new AuthController(), 'logout'])->add($authMiddleware);
$app->get('/me', [new AuthController(), 'me'])->add($authMiddleware);

$app->group('/users', function ($group) {
    $group->get('', [new UserController(), 'index']);
    $group->post('', [new UserController(), 'store']);
    $group->put('/{id}', [new UserController(), 'update']);
    $group->put('/{id}/role', [new UserController(), 'changeRole']);
})->add($adminMiddleware)->add($authMiddleware);

$app->run();
