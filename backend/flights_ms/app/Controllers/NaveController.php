<?php

namespace App\Controllers;

use App\Models\Nave;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class NaveController
{
    public function index(Request $request, Response $response): Response
    {
        $response->getBody()->write(json_encode(['naves' => Nave::all()]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $nave = Nave::create($data);
        $response->getBody()->write(json_encode(['nave' => $nave]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $nave = Nave::find($args['id']);
        if (!$nave) {
            $response->getBody()->write(json_encode(jsonError('Nave no encontrada', 404)));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $nave->fill($request->getParsedBody());
        $nave->save();

        $response->getBody()->write(json_encode(['nave' => $nave]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $nave = Nave::find($args['id']);
        if (!$nave) {
            $response->getBody()->write(json_encode(jsonError('Nave no encontrada', 404)));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $nave->delete();
        $response->getBody()->write(json_encode(['message' => 'Nave eliminada']));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
