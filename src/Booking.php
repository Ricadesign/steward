<?php

namespace Ricadesign\Steward;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ricadesign\Steward\Database\Factories\BookingFactory;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = ['adults', 'childs', 'reservation_at', 'shift', 'name', 'phone', 'email', 'observations'];

    protected static function newFactory()
    {
        return BookingFactory::new();
    }

    public function tables()
    {
        return $this->belongsToMany(Table::class);
    }

}
