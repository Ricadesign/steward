<?php

namespace Ricadesign\Steward\Tests;

use App\Models\Reservation;
use Faker\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Ricadesign\Steward\Booking;
use Ricadesign\Steward\BookingService;
use Ricadesign\Steward\Table;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    public BookingService $bookingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bookingService = new BookingService();
    }
    /**
     * A basic feature test example.
     *
     * @return void
     */

    public function test_simple_booking()
    {
        //Arrange
        //Act
        $this->bookingService->makeBooking(4, new Carbon(), 'night');
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
        $num = 4;
        $oldBooking = Booking::factory()->hasAttached(Table::find(4))->create([
            'num' => $num,
            'shift' => 'night',
            'reservation_at' => $date,
        ]);


        //Act
        $booking = $this->bookingService->makeBooking($num, $date, 'night');
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
            'num' => 12,
            'shift' => 'night',
            'reservation_at' => $date,
        ]);
        //Act
        $booking = $this->bookingService->makeBooking(4, $date, 'night');
        //Assert
        $this->assertDatabaseCount('bookings', 2);
        $this->assertEquals($booking->reservation_at, $oldBooking->reservation_at);
        $this->assertEquals($booking->shift, $oldBooking->shift);
        $this->assertNotEquals($booking->tables()->first()->id, $oldBooking->tables()->first()->id);
        $this->assertCount(1, $booking->tables);
        $this->assertEquals(6, $booking->tables()->first()->size);//Get biggest table
    }

    public function test_booking_with_no_same_size_table_gets_two_tables()
    {
        //Arrange
        $table = Table::where('size', 6)->first();
        $date = Carbon::createFromTime(22);
        $oldBooking = Booking::factory()->hasAttached($table)->create([
            'num' => 5,
            'shift' => 'night',
            'reservation_at' => $date,
        ]);
        $oldBooking8= Booking::factory()->hasAttached( Table::where('size', 8)->first())->create([
            'num' => 5,
            'shift' => 'night',
            'reservation_at' => $date,
        ]);

        //Act
        $booking = $this->bookingService->makeBooking(6, $date, 'night');

        //Assert
        $this->assertDatabaseCount('bookings', 2);
        $this->assertEquals($booking->reservation_at, $oldBooking->reservation_at);
        $this->assertEquals($booking->shift, $oldBooking->shift);
        $this->assertNotEquals($booking->tables()->first()->id, $oldBooking->tables()->first()->id);
        $this->assertCount(1, $booking->tables);
        $this->assertEquals(6, $booking->tables()->first()->size);//Get biggest table
    }
}
