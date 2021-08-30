<?php

namespace Ricadesign\Steward;

use Exception;
use Illuminate\Support\Carbon;

class BookingService 
{


    public function makeBooking(int $num, Carbon $timestamp, string $shift): Booking
    {
        $tables = Table::where('size', '>=', $num)->orderBy($num)->notReserved($timestamp, $shift);
        $table = $tables->first();

        if($table == null) { //Check multiple table
            $tables = Table::notReserved($timestamp, $shift)->orderBy($num, 'desc')->get();
            if($tables->sum('size') < $num ){
                throw new Exception("Insuficient tables for booking", 1);
                //Ok
            }

            // if($tables->count() < 2){
            //     //Do something
            // } else {
            //     $selectedTables = collect([$tables->shift()]);
            //     $selectedTable = null;
            //     $selectedDiff = null;
            //     foreach ($tables as $table) {
            //         if($table->size + $selectedTables->sum('size') < $num){
            //             $selectedTables->push($table);
            //         } else {
            //             $diff = $num - $table->size + $selectedTables->sum('size');
            //             if($selectedDiff == null || $diff < $selectedDiff){
            //                 $selectedTable
            //             }
            //         }

            //     }
            // }
            

        }


        //Make booking
        $booking = new Booking();
        $booking ->num = $num;
        $booking ->shift = $shift;
        $booking ->reservation_at = $timestamp;
        if($table) {
            $table->bookings()->save($booking);
        }
        return $booking;

    }
}
