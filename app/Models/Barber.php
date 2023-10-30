<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barber extends Model
{
    use HasFactory;

    // RELATION

    public function services()
    {
        return $this->hasMany(BarberService::class, 'barber_id', 'id');
    }

    // ACCESSOR
    protected function image(): Attribute
    {
        return Attribute::make(
            fn ($value) => !empty($value)?asset($value):'',
        );
    }
}
