<?php

namespace App\Controllers;

use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController
{
    public function register(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $user = User::create([
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? '',
            'password' => password_hash($data['password'] ?? '', PASSWORD_BCRYPT),
            'role' => $data['role'] ?? 'gestor',
        ]);

        $response->getBody()->write(json_encode(['user' => $user]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    public function index(Response $response): Response
    {
        $users = User::all(['id', 'name', 'email', 'role', 'token']);
        $response->getBody()->write(json_encode(['users' => $users]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $user = User::find($args['id']);
        if (!$user) {
            $response->getBody()->write(json_encode(jsonError('Usuario no encontrado', 404)));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $data = $request->getParsedBody();
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        } else {
            unset($data['password']);
        }
        $user->fill($data);
        $user->save();

        $response->getBody()->write(json_encode(['user' => $user]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function changeRole(Request $request, Response $response, array $args): Response
    {
        $user = User::find($args['id']);
        if (!$user) {
            $response->getBody()->write(json_encode(jsonError('Usuario no encontrado', 404)));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $data = $request->getParsedBody();
        $role = $data['role'] ?? null;
        if (!$role) {
            $response->getBody()->write(json_encode(jsonError('Rol requerido')));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $user->role = $role;
        $user->save();

        $response->getBody()->write(json_encode(['user' => $user]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
