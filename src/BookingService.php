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

    public function makeBooking(array $bookingData): Booking
    {
        $this->findAvailableTables(
            $bookingData['adults'] + $bookingData['childs'],
            $bookingData['reservation_at'],
            $bookingData['shift'],
        );

        // Make booking
        $booking = Booking::create($bookingData);
        $booking->tables()->attach($this->tablesToBeBooked);

        return $booking;
    }

    private function findAvailableTables(int $guestsCount, Carbon $timestamp, string $shift)
    {
        // Check single table
        $singleTable = Table::where('size', '>=', $guestsCount)
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

        if ($tables->sum('size') < $guestsCount){
            throw new Exception("Insuficient tables for booking", 1);
        }

        $tableGroupsSize = 2;
        while ($this->validCombinationsOfTables->isEmpty() && $tableGroupsSize <= $tables->count()) {
            $this->fillValidCombinationsOfTables($tables->all(), $tableGroupsSize, $guestsCount);
            $tableGroupsSize++;
        }

        $this->validCombinationsOfTables = $this->validCombinationsOfTables->sortBy([
            function ($a, $b) {
                return $a->sum('size') < $b->sum('size') ? -1 : 1;
            }
        ]);

        $this->tablesToBeBooked = $this->validCombinationsOfTables->first();
    }

    private function fillValidCombinationsOfTables(array $availableTables, int $groupsSize, int $guestsCount)
    {
        $this->combineUntil($availableTables, [], 0, count($availableTables) - 1, 0, $groupsSize, $guestsCount);
    }

    private function combineUntil($arr, $data, $start, $end, $index, $groupsSize, $guestsCount)
    {
        if (collect($data)->sum('size') >= $guestsCount) {
            $this->validCombinationsOfTables->push((new Table)->newCollection($data));
            return;
        } elseif ($index === $groupsSize) {
            return;
        }

        for ($i = $start; $i <= $end && $end - $i + 1 >= $groupsSize - $index; $i++) {
            $data[$index] = $arr[$i];
            $this->combineUntil($arr, $data, $i + 1, $end, $index + 1, $groupsSize, $guestsCount);
        }
    }
}
