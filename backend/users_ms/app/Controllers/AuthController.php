<?php

namespace App\Controllers;

use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        // Permitir los nombres de campos del ejemplo del profesor (user/pwd) además de email/password
        $email = $data['email'] ?? $data['user'] ?? '';
        $password = $data['password'] ?? $data['pwd'] ?? '';

        $user = User::where('email', $email)->first();
        if (!$user || (!password_verify($password, $user->password) && $user->password !== $password)) {
            $response->getBody()->write(json_encode(['error' => 'Credenciales inválidas']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $token = bin2hex(random_bytes(32));
        $user->token = $token;
        $user->save();

        $response->getBody()->write(json_encode([
            'token' => $token,
            'role' => $user->role,
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function logout(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        if ($user) {
            $user->token = null;
            $user->save();
        }

        $response->getBody()->write(json_encode(['message' => 'Sesión cerrada']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function me(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $response->getBody()->write(json_encode([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
