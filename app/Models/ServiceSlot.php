<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceSlot extends Model
{
    use HasFactory;

    // RELATION

    public function service()
    {
        return $this->belongsTo(BarberService::class, 'service_id', 'id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'slot_id', 'id');
    }
}
