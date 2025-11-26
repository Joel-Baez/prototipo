<?php

use App\Controllers\AuthController;
use App\Controllers\UserController;
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
    $response->getBody()->write(json_encode(['service' => 'users_ms', 'status' => 'ok']));
    return $response->withHeader('Content-Type', 'application/json');
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
