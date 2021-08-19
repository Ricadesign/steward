<?php

namespace App\Http\Controllers;

use App\Mail\ReservationBooked;
use App\Mail\ReservationBookedAdmin;
use App\Models\Reservation;
use App\Models\Table;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WizardController extends Controller
{
    private $dinner_guests;

    public function index($step)
    {
        switch ($step) {
            case 'comfirmed':
                return redirect()->route('home');
                break;
            case 'personal':
                return redirect()->route('wizard', ['step' => 'table']);
                break;
            default:
                return view('pages.wizard');
                break;
        }
    }

    public function store(Request $request)
    {
        try {
            $wizard =  json_decode($request->all()['wizard']);

            if (!$this->checkIfAvailableTable(
                $wizard->date,
                $wizard->hour->shift,
                $this->roundUpEven(
                    $wizard->adults,
                    $wizard->childs
                )
            )) throw new Exception('wizard.no_table', 1);

            $reservation = Reservation::createReservation($wizard->adults, $wizard->childs, $wizard->date, $wizard->hour, $wizard->name, $wizard->email, $wizard->phone, $wizard->observations);


            Mail::to(config('mail.contact'))->send(new ReservationBooked($reservation));
            Mail::to($reservation->email)->send(new ReservationBookedAdmin($reservation));

            return 'Ok';
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    private function checkIfAvailableTable($reservation_date, $shift, $dinner_guests)
    {
        $this->dinner_guests = $dinner_guests;

        $reservation_date = new Carbon($reservation_date);

        $reservationsThatDay = Reservation::where('reservation_date', $reservation_date->toDateString())->where('shift', $shift)->get();

        if (count($reservationsThatDay) == 0) return true;

        $reservationsForThatTable = $reservationsThatDay->filter(function ($reservation) {
            return $reservation->dinner_guests == $this->dinner_guests;
        });

        if (count($reservationsForThatTable) == 0) return true;

        $tables = Table::where('dinner_guests', $dinner_guests)->get();

        if (count($reservationsForThatTable) >= count($tables) || count($tables) == 0) return false;

        return true;
    }

    private function roundUpEven($adults, $childs)
    {
        $total = $adults + $childs;

        //Round up even
        $total % 2 == 1 ?  $total++ : '';

        return $total;
    }

}
