<?php

namespace Ricadesign\Steward;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ricadesign\Steward\Database\Factories\BookingFactory;

class Booking extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::deleted(function ($booking) {
            $booking->tables()->detach();
        });
    }

    protected $casts = [
        'reservation_at' => 'datetime',
    ];

    protected static function newFactory()
    {
        return BookingFactory::new();
    }

    public function tables()
    {
        return $this->belongsToMany(Table::class);
    }

}
