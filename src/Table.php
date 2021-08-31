<?php

namespace Ricadesign\Steward;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Table extends Model
{
    use HasFactory;

    protected $casts = [
        'size' => 'integer'
    ];

    protected static function newFactory()
    {
        return \Ricadesign\Steward\Database\Factories\TableFactory::new();
    }

    public function scopeNotReserved($query, Carbon $timestamp, $shift)
    {
        return $query->whereDoesntHave('bookings', function ($query) use ($shift, $timestamp) {
            $query->where('shift', $shift)->whereDate('reservation_at', $timestamp);
        });
    }

    public function bookings()
    {
        return $this->belongsToMany(Booking::class);
    }
}
