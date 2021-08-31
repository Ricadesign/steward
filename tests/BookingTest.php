<?php

namespace Ricadesign\Steward\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Ricadesign\Steward\Booking;
use Ricadesign\Steward\BookingService;
use Ricadesign\Steward\Table;
use Illuminate\Support\Facades\DB;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    public BookingService $bookingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bookingService = new BookingService();
    }

    private function makeBooking(array $bookingData)
    {
        return $this->bookingService->makeBooking(array_merge([
            'adults' => 2,
            'childs' => 0,
            'reservation_at' => Carbon::createFromTime(22),
            'shift' => 'night',
            'name' => 'John',
            'phone' => '555-555-555',
            'email' => 'john@test.com',
        ], $bookingData));
    }

    public function test_simple_booking()
    {
        //Act
        $this->makeBooking([
            'adults' => 4,
            'reservation_at' => Carbon::createFromTime(22),
            'shift' => 'night',
        ]);

        //Assert
        $bookings = Booking::all();
        $this->assertDatabaseCount('bookings', 1);
        $tables = $bookings->first()->tables;
        $this->assertCount(1, $tables);
        $this->assertEquals(4, $tables->first()->size);
    }

    public function test_booking_with_reservations()
    {
        //Arrange
        $date = Carbon::createFromTime(22);
        $adults = 4;
        $oldBooking = Booking::factory()->hasAttached(Table::find(4))->create([
            'adults' => 4,
            'shift' => 'night',
            'reservation_at' => $date,
        ]);

        //Act
        $booking = $this->makeBooking([
            'adults' => $adults,
            'reservation_at' => $date,
            'shift' => 'night',
        ]);

        //Assert
        $this->assertDatabaseCount('bookings', 2);
        $this->assertEquals($booking->reservation_at, $oldBooking->reservation_at);
        $this->assertEquals($booking->shift, $oldBooking->shift);
        $this->assertNotEquals($booking->tables()->first()->id, $oldBooking->tables()->first()->id);
        $this->assertCount(1, $booking->tables);
        $this->assertEquals(4, $booking->tables()->first()->size);
    }

    public function test_booking_with_no_same_size_table_books_next_biggest()
    {
        //Arrange
        $tables = Table::where('size', 4)->get();
        $date = Carbon::createFromTime(22);
        $oldBooking = Booking::factory()->hasAttached($tables)->create([
            'shift' => 'night',
            'reservation_at' => $date,
        ]);

        //Act
        $booking = $this->makeBooking([
            'adults' => 4,
            'reservation_at' => $date,
            'shift' => 'night',
        ]);

        //Assert
        $this->assertDatabaseCount('bookings', 2);
        $this->assertEquals($booking->reservation_at, $oldBooking->reservation_at);
        $this->assertEquals($booking->shift, $oldBooking->shift);
        $this->assertNotEquals($booking->tables()->first()->id, $oldBooking->tables()->first()->id);
        $this->assertCount(1, $booking->tables);
        $this->assertEquals(6, $booking->tables()->first()->size);//Get biggest table
    }

    public function test_booking_with_no_same_size_or_bigger_table_gets_a_combination_of_tables()
    {
        //Arrange
        Table::factory(1)->create(['size' => 8]);

        //Act
        $booking = $this->makeBooking(['adults' => 16]);

        //Assert
        $this->assertDatabaseCount('bookings', 1);
        $this->assertEquals([8, 8], $booking->tables()->pluck('size')->all());
    }

    public function test_it_chooses_the_combination_of_tables_with_the_smallest_difference()
    {
        //Arrange
        DB::table('tables')->truncate();
        Table::factory()->create(['size' => 7]);
        Table::factory()->create(['size' => 10]);
        Table::factory()->create(['size' => 8]);

        //Act
        $booking = $this->makeBooking(['adults' => 14]);

        //Assert
        $this->assertDatabaseCount('bookings', 1);
        $this->assertCount(2, $booking->tables);
        $this->assertContains(7, $booking->tables()->pluck('size')->all());
        $this->assertContains(8, $booking->tables()->pluck('size')->all());
    }

    public function test_it_throws_an_exception_if_not_enough_tables_are_available()
    {
        $this->expectException(\Exception::class);

        //Act
        $this->makeBooking(['adults' => 100]);

        //Assert
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_it_gets_groups_of_more_than_two_tables_when_needed()
    {
        //Arrange
        Table::factory()->count(4)->create(['size' => 10]);

        //Act
        $booking = $this->makeBooking(['adults' => 40]);

        //Assert
        $this->assertDatabaseCount('bookings', 1);
        $this->assertEquals([10, 10, 10, 10], $booking->tables()->pluck('size')->all());
    }

    public function test_it_books_as_few_tables_as_possible()
    {
        //Arrange
        Table::factory()->count(3)->create(['size' => 10]);
        Table::factory()->count(2)->create(['size' => 15]);

        //Act
        $booking = $this->makeBooking(['adults' => 30]);

        //Assert
        $this->assertDatabaseCount('bookings', 1);
        $this->assertEquals([15, 15], $booking->tables()->pluck('size')->all());
    }
}
