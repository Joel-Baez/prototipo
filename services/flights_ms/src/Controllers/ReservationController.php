<?php

namespace App\Controllers;

use App\Models\Flight;
use App\Models\Reservation;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ReservationController
{
    public function index(Response $response): Response
    {
        $reservations = Reservation::with('flight')->get();
        $response->getBody()->write(json_encode(['reservations' => $reservations]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function byUser(Response $response, array $args): Response
    {
        $reservations = Reservation::where('user_id', $args['userId'])->with('flight')->get();
        $response->getBody()->write(json_encode(['reservations' => $reservations]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $flight = Flight::find($data['flight_id'] ?? 0);
        if (!$flight) {
            $response->getBody()->write(json_encode(jsonError('Vuelo no encontrado', 404)));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $reservation = Reservation::create([
            'user_id' => $data['user_id'] ?? $request->getAttribute('user')->id,
            'flight_id' => $flight->id,
            'status' => 'activa',
        ]);

        $response->getBody()->write(json_encode(['reservation' => $reservation]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    public function cancel(Response $response, array $args): Response
    {
        $reservation = Reservation::find($args['id']);
        if (!$reservation) {
            $response->getBody()->write(json_encode(jsonError('Reserva no encontrada', 404)));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $reservation->status = 'cancelada';
        $reservation->save();

        $response->getBody()->write(json_encode(['reservation' => $reservation]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
