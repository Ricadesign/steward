<?php

namespace Ricadesign\Steward;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class BookingService
{
    private Collection $validCombinationsOfTables;
    private EloquentCollection $tablesToBeBooked;

    public function __construct()
    {
        $this->validCombinationsOfTables = collect();
        $this->tablesToBeBooked = (new Table)->newCollection();
    }

    public function makeBooking(int $num, Carbon $timestamp, string $shift): Booking
    {
        $this->findAvailableTables($num, $timestamp, $shift);

        //Make booking
        $booking = new Booking();
        $booking->num = $num;
        $booking->shift = $shift;
        $booking->reservation_at = $timestamp;
        $booking->save();

        $booking->tables()->attach($this->tablesToBeBooked);

        return $booking;

    }

    private function findAvailableTables(int $num, Carbon $timestamp, string $shift)
    {
        // Check single table
        $singleTable = Table::where('size', '>=', $num)
            ->notReserved($timestamp, $shift)
            ->orderBy('size')
            ->first();

        if ($singleTable) {
            $this->tablesToBeBooked->push($singleTable);
            return;
        }

        // Check multiple tables
        $tables = Table::notReserved($timestamp, $shift)
            ->orderBy('size', 'desc')
            ->get();

        if ($tables->sum('size') < $num){
            throw new Exception("Insuficient tables for booking", 1);
        }

        $tableGroupsSize = 2;
        while ($this->validCombinationsOfTables->isEmpty() && $tableGroupsSize <= $tables->count()) {
            $this->fillValidCombinationsOfTables($tables->all(), $tableGroupsSize, $num);
            $tableGroupsSize++;
        }

        $this->validCombinationsOfTables = $this->validCombinationsOfTables->sortBy([
            function ($a, $b) {
                return $a->sum('size') < $b->sum('size') ? -1 : 1;
            }
        ]);

        $this->tablesToBeBooked = $this->validCombinationsOfTables->first();
    }

    private function fillValidCombinationsOfTables(array $availableTables, int $groupsSize, int $totalNeeded)
    {
        $this->combineUntil($availableTables, [], 0, count($availableTables) - 1, 0, $groupsSize, $totalNeeded);
    }

    private function combineUntil($arr, $data, $start, $end, $index, $groupsSize, $totalNeeded)
    {
        if (collect($data)->sum('size') >= $totalNeeded) {
            $this->validCombinationsOfTables->push((new Table)->newCollection($data));
            return;
        } elseif ($index === $groupsSize) {
            return;
        }

        for ($i = $start; $i <= $end && $end - $i + 1 >= $groupsSize - $index; $i++) {
            $data[$index] = $arr[$i];
            $this->combineUntil($arr, $data, $i + 1, $end, $index + 1, $groupsSize, $totalNeeded);
        }
    }
}
