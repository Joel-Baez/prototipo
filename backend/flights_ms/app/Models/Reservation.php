<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $table = 'reservations';
    protected $fillable = ['user_id', 'flight_id', 'status'];

    public function flight()
    {
        return $this->belongsTo(Flight::class, 'flight_id');
    }
}
