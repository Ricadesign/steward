<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    
    public static function seekTable($adults, $childs)
    {
        $total = $adults + $childs;

        //Round up even
        $total % 2 == 1 ?  $total++ : '';

        $table = Table::where('dinner_guests', $total)->first();

        return $table;

    }
}
