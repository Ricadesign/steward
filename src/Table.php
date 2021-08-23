<?php

namespace Ricadesign\Steward;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Ricadesign\Steward\Database\Factories\TableFactory::new();
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
