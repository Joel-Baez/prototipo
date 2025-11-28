<?php

namespace App\Controllers;

use App\Models\Flight;
use App\Models\Nave;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FlightController
{
    public function index(Response $response): Response
    {
        $flights = Flight::with('nave')->get();
        $response->getBody()->write(json_encode(['flights' => $flights]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function search(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $query = Flight::query();

        if (!empty($params['origin'])) {
            $query->where('origin', 'like', "%{$params['origin']}%");
        }
        if (!empty($params['destination'])) {
            $query->where('destination', 'like', "%{$params['destination']}%");
        }
        if (!empty($params['date'])) {
            $query->whereDate('departure', $params['date']);
        }

        $flights = $query->with('nave')->get();
        $response->getBody()->write(json_encode(['flights' => $flights]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $nave = Nave::find($data['nave_id'] ?? 0);
        if (!$nave) {
            $response->getBody()->write(json_encode(jsonError('Nave no encontrada', 404)));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $flight = Flight::create($data);
        $response->getBody()->write(json_encode(['flight' => $flight]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $flight = Flight::find($args['id']);
        if (!$flight) {
            $response->getBody()->write(json_encode(jsonError('Vuelo no encontrado', 404)));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $data = $request->getParsedBody();
        if (isset($data['nave_id']) && !Nave::find($data['nave_id'])) {
            $response->getBody()->write(json_encode(jsonError('Nave no encontrada', 404)));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $flight->fill($data);
        $flight->save();

        $response->getBody()->write(json_encode(['flight' => $flight]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(Response $response, array $args): Response
    {
        $flight = Flight::find($args['id']);
        if (!$flight) {
            $response->getBody()->write(json_encode(jsonError('Vuelo no encontrado', 404)));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $flight->delete();
        $response->getBody()->write(json_encode(['message' => 'Vuelo eliminado']));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
