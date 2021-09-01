<?php

namespace Ricadesign\Steward;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Carbon\CarbonImmutable;

class BookingService
{
    private Collection $validCombinationsOfTables;
    private EloquentCollection $tablesToBeBooked;

    public function __construct()
    {
        $this->initialize();
    }

    private function initialize()
    {
        $this->validCombinationsOfTables = collect();
        $this->tablesToBeBooked = (new Table)->newCollection();
    }

    public function findAvailableDatesForTwoWeeks(int $people, CarbonImmutable $startDate)
    {
        $maxCapacity = Table::all()->sum('size');
        $startDate = $startDate->startOfDay();
        $endDate = $startDate->addDays(13);

        $seatsAvailableForTheNextTwoWeeks = Booking::with('tables')
            ->whereBetween('reservation_at', [$startDate, $endDate])->get()
            ->groupBy(function($booking) {
                return $booking->reservation_at->format('Y-m-d');
            })->map(function($bookingsForADate) use ($maxCapacity) {
                return $bookingsForADate->reduce(function($carry, $booking) {
                    $shift = $booking->shift;
                    $carry[$shift] -= $booking->tables->sum('size');
                    return $carry;
                }, ['midday' => $maxCapacity, 'night' => $maxCapacity]);
            });

        $date = $startDate;
        $shiftAvailabilityForTheNextTwoWeeks = [];
        while ($date <= $endDate) {
            $dateString = $date->format('Y-m-d');
            $shiftAvailabilityForTheNextTwoWeeks[] = [
                'date' => $dateString,
                'availability' => [
                    ...(! isset($seatsAvailableForTheNextTwoWeeks[$dateString]) || $seatsAvailableForTheNextTwoWeeks[$dateString]['midday'] >= $people ? ['midday'] : []),
                    ...(! isset($seatsAvailableForTheNextTwoWeeks[$dateString]) || $seatsAvailableForTheNextTwoWeeks[$dateString]['night'] >= $people ? ['night'] : []),
                ]
            ];
            $date = $date->addDay();
        }

        return $shiftAvailabilityForTheNextTwoWeeks;
    }

    public function makeBooking(array $bookingData): Booking
    {
        $this->findAvailableTables(
            $bookingData['people'],
            $bookingData['reservation_at'],
            $bookingData['shift'],
        );

        // Make booking
        $booking = Booking::create($bookingData);
        $booking->tables()->attach($this->tablesToBeBooked);

        return $booking;
    }

    private function findAvailableTables(int $guestsCount, Carbon $date, string $shift)
    {
        $this->initialize();

        // Check single table
        $singleTable = Table::where('size', '>=', $guestsCount)
            ->notReserved($date, $shift)
            ->orderBy('size')
            ->first();

        if ($singleTable) {
            $this->tablesToBeBooked->push($singleTable);
            return;
        }

        // Check multiple tables
        $tables = Table::notReserved($date, $shift)->get();

        if ($tables->sum('size') < $guestsCount) {
            throw new Exception("Insuficient tables for booking", 1);
        }

        $tableGroupsSize = 2;
        while ($this->validCombinationsOfTables->isEmpty() && $tableGroupsSize <= $tables->count()) {
            $this->fillValidCombinationsOfTables($tables->all(), $tableGroupsSize, $guestsCount);
            $tableGroupsSize++;
        }

        $this->validCombinationsOfTables = $this->validCombinationsOfTables->sortBy([
            function ($a, $b) {
                return (
                    $a->sum('size') < $b->sum('size') ||
                    ($a->sum('size') === $b->sum('size') && $a->max('size') > $b->max('size'))
                ) ? -1 : 1;
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
