<?php

namespace App\Models;

use App\Mail\ReservationComfirmed;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class Reservation extends Model
{
    use HasFactory;

    protected $appends = ['dinner_guests'];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public static function createReservation($adults, $childs, $reservation_date, $hour, $name, $email, $phone, $observations)
    {
        $reservation = new Reservation();

        $reservation->adults = $adults;
        $reservation->childs = $childs;
        $reservation->reservation_date = new Carbon($reservation_date);
        $reservation->hour = $hour->hour;
        $reservation->shift = $hour->shift;
        $reservation->name = $name;
        $reservation->email = $email;
        $reservation->phone = $phone;
        $reservation->observations = $observations;

        $reservation->save();
        
        return $reservation;
    }

    public function getDinnerGuestsAttribute()
    {
        $total = $this->adults + $this->childs;

        //Round up even
        $total % 2 == 1 ?  $total++ : '';

        return $total;
    }

    public function comfirmReservation()
    {
        $this->status = 'comfirmed';
        $this->save();
        Mail::to($this->email)->send(new ReservationComfirmed($this));
    }


}
