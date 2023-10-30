<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarberService extends Model
{
    use HasFactory;

    // RELATION
    public function barber()
    {
        return $this->belongsTo(Barber::class, 'barber_id', 'id');
    }

    public function timeslots()
    {
        return $this->hasMany(ServiceSlot::class, 'service_id', 'id');
    }

    // ACCESSOR
    protected function image(): Attribute
    {
        return Attribute::make(
            fn ($value) => !empty($value)?asset($value):'',
        );
    }
}
