<?php

namespace Ricadesign\Steward\Tests;

use Ricadesign\Steward\Table;
use Ricadesign\Steward\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TableTest extends TestCase
{
    use RefreshDatabase;

    public function test_tables_without_a_booking_for_a_certain_date_and_shift_are_not_reserved()
    {
        $now = now();
        $table = Table::whereSize(4)->first();
        $tablesCount = Table::count();
        $notReservedTables = Table::notReserved($now, 'night')->get();

        $this->assertCount($tablesCount, $notReservedTables);
        $this->assertTrue($notReservedTables->contains($table));

        Booking::factory()->hasAttached($table)->create([
            'people' => 4,
            'shift' => 'night',
            'reservation_at' => $now,
        ]);

        $notReservedTables = Table::notReserved($now, 'night')->get();

        $this->assertCount($tablesCount - 1, $notReservedTables);
        $this->assertFalse($notReservedTables->contains($table));
    }
}