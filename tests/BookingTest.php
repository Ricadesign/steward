<?php

namespace Ricadesign\Steward\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Ricadesign\Steward\Booking;
use Ricadesign\Steward\BookingService;
use Ricadesign\Steward\Table;
use Illuminate\Support\Facades\DB;
use ReflectionObject;

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
            'people' => 2,
            'reservation_at' => Carbon::createFromTime(22),
            'shift' => 'night',
            'name' => 'John',
            'phone' => '555-555-555',
            'email' => 'john@test.com',
        ], $bookingData));
    }

    private function getTableSizesFor(Booking $booking)
    {
        return $booking->tables->pluck('size')->sort()->values()->all();
    }

    public function test_it_returns_an_array_with_availability_per_shift_info_for_the_next_two_weeks()
    {
        // Act
        $availableDates = $this->bookingService->findAvailableDatesForTwoWeeks(2, CarbonImmutable::now());

        // Assert
        $this->assertCount(14, $availableDates);
        $expectedArray = [];
        $date = CarbonImmutable::now();
        $endDate = $date->addDays(13);
        while ($date <= $endDate) {
            $expectedArray[] = [
                'date' => $date->format('Y-m-d'),
                'availability' => ['midday', 'night'],
            ];
            $date = $date->addDay();
        }
        $this->assertSame($expectedArray, $availableDates);
    }

    public function test_it_returns_an_empty_array_for_days_where_both_shifts_are_unavailable()
    {
        // Arrange
        $tables = Table::all();
        Booking::factory()->hasAttached($tables)->create([
            'people' => $tables->sum('size'),
            'shift' => 'midday',
            'reservation_at' => now()->addDay(),
        ]);
        Booking::factory()->hasAttached($tables)->create([
            'people' => $tables->sum('size'),
            'shift' => 'night',
            'reservation_at' => now()->addDay(),
        ]);

        // Act
        $availableDates = $this->bookingService->findAvailableDatesForTwoWeeks(2, CarbonImmutable::now());

        // Assert
        $this->assertCount(14, $availableDates);
        $expectedArray = [];
        $date = $startDate = CarbonImmutable::now();
        $endDate = $date->addDays(13);
        while ($date <= $endDate) {
            $expectedArray[] = [
                'date' => $date->format('Y-m-d'),
                'availability' => $date->eq($startDate->addDay()) ? [] : ['midday', 'night'],
            ];
            $date = $date->addDay();
        }

        $this->assertSame($expectedArray, $availableDates);
    }

    public function test_a_shift_is_unavailable_if_there_are_not_enough_tables_left_for_the_guests_requested()
    {
        // Arrange
        $tables = Table::all();
        $allTablesButASize4 = $tables->reject(function($table) use ($tables) {
            return $table->size === 4 && $tables->firstWhere('size', 4) === $table;
        });
        Booking::factory()->hasAttached($allTablesButASize4)->create([
            'people' => $tables->sum('size') - 4,
            'shift' => 'midday',
            'reservation_at' => now()->addDay(),
        ]);
        Booking::factory()->hasAttached($allTablesButASize4)->create([
            'people' => $tables->sum('size') - 4,
            'shift' => 'night',
            'reservation_at' => now()->addDay(),
        ]);

        // Act
        $availableDates = $this->bookingService->findAvailableDatesForTwoWeeks(5, CarbonImmutable::now());

        $this->assertCount(14, $availableDates);
        $expectedArray = [];
        $date = $startDate = CarbonImmutable::now();
        $endDate = $date->addDays(13);
        while ($date <= $endDate) {
            $expectedArray[] = [
                'date' => $date->format('Y-m-d'),
                'availability' => $date->eq($startDate->addDay()) ? [] : ['midday', 'night'],
            ];
            $date = $date->addDay();
        }

        $this->assertSame($expectedArray, $availableDates);
    }

    public function test_a_shift_is_available_if_there_are_just_enough_tables_left_for_the_guests_requested()
    {
        // Arrange
        $tables = Table::all();
        $allTablesButASize4 = $tables->reject(function($table) use ($tables) {
            return $table->size === 4 && $tables->firstWhere('size', 4) === $table;
        });
        Booking::factory()->hasAttached($allTablesButASize4)->create([
            'people' => $tables->sum('size') - 4,
            'shift' => 'midday',
            'reservation_at' => now()->addDay(),
        ]);
        Booking::factory()->hasAttached($allTablesButASize4)->create([
            'people' => $tables->sum('size') - 4,
            'shift' => 'night',
            'reservation_at' => now()->addDay(),
        ]);

        // Act
        $availableDates = $this->bookingService->findAvailableDatesForTwoWeeks(4, CarbonImmutable::now());

        $this->assertCount(14, $availableDates);
        $expectedArray = [];
        $date = CarbonImmutable::now();
        $endDate = $date->addDays(13);
        while ($date <= $endDate) {
            $expectedArray[] = [
                'date' => $date->format('Y-m-d'),
                'availability' => ['midday', 'night'],
            ];
            $date = $date->addDay();
        }

        $this->assertSame($expectedArray, $availableDates);
    }

    public function test_a_shift_is_available_if_there_are_more_seats_left_available_than_guests_requested()
    {
        // Arrange
        $tables = Table::all();
        $allTablesButASize4 = $tables->reject(function($table) use ($tables) {
            return $table->size === 4 && $tables->firstWhere('size', 4) === $table;
        });
        Booking::factory()->hasAttached($allTablesButASize4)->create([
            'people' => $tables->sum('size') - 4,
            'shift' => 'midday',
            'reservation_at' => now()->addDay(),
        ]);
        Booking::factory()->hasAttached($allTablesButASize4)->create([
            'people' => $tables->sum('size') - 4,
            'shift' => 'night',
            'reservation_at' => now()->addDay(),
        ]);

        // Act
        $availableDates = $this->bookingService->findAvailableDatesForTwoWeeks(3, CarbonImmutable::now());

        $this->assertCount(14, $availableDates);
        $expectedArray = [];
        $date = CarbonImmutable::now();
        $endDate = $date->addDays(13);
        while ($date <= $endDate) {
            $expectedArray[] = [
                'date' => $date->format('Y-m-d'),
                'availability' => ['midday', 'night'],
            ];
            $date = $date->addDay();
        }

        $this->assertSame($expectedArray, $availableDates);
    }

    public function test_simple_booking()
    {
        //Act
        $this->makeBooking([
            'people' => 4,
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
        $people = 4;
        $oldBooking = Booking::factory()->hasAttached(Table::find(4))->create([
            'people' => 4,
            'shift' => 'night',
            'reservation_at' => $date,
        ]);

        //Act
        $booking = $this->makeBooking([
            'people' => $people,
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
            'people' => 4,
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
        Table::factory()->create(['size' => 8]);

        //Act
        $booking = $this->makeBooking(['people' => 16]);

        //Assert
        $this->assertDatabaseCount('bookings', 1);
        $this->assertEquals([8, 8], $this->getTableSizesFor($booking));
    }

    public function test_it_chooses_the_combination_of_tables_with_the_smallest_difference()
    {
        // Arrange
        DB::table('tables')->truncate();
        Table::factory()->create(['size' => 7]);
        Table::factory()->create(['size' => 10]);
        Table::factory()->create(['size' => 8]);

        // Act
        $booking = $this->makeBooking(['people' => 14]);

        // Assert
        $this->assertDatabaseCount('bookings', 1);
        $this->assertCount(2, $booking->tables);
        $this->assertEquals([7, 8], $this->getTableSizesFor($booking));
    }

    public function test_when_two_or_more_combinations_have_the_same_difference_it_books_the_combination_with_the_largest_table()
    {
        // Act
        $booking = $this->makeBooking(['people' => 10]);
        $bookingService = new ReflectionObject($this->bookingService);
        $combinations = $bookingService->getProperty('validCombinationsOfTables');
        $combinations->setAccessible(true);
        $combinations = $combinations->getValue($this->bookingService);

        // Assert
        $this->assertDatabaseCount('bookings', 1);
        // Check if 4+6 is within the possible combinations
        $this->assertTrue($combinations->contains(function($combination) {
            return $combination->count() === 2 &&
                $combination->pluck('size')->contains(4) &&
                $combination->pluck('size')->contains(6);
        }));
        $this->assertEquals([2, 8], $this->getTableSizesFor($booking));
    }

    public function test_it_gets_groups_of_more_than_two_tables_when_needed()
    {
        //Act
        $booking = $this->makeBooking(['people' => 18]);

        //Assert
        $this->assertDatabaseCount('bookings', 1);
        $this->assertEquals([4, 6, 8], $this->getTableSizesFor($booking));
    }

    public function test_it_books_as_few_tables_as_possible()
    {
        //Act
        $booking1 = $this->makeBooking(['people' => 14]);
        $booking2 = $this->makeBooking(['people' => 14]);

        //Assert
        $this->assertDatabaseCount('bookings', 2);
        $this->assertEquals([6, 8], $this->getTableSizesFor($booking1));
        $this->assertEquals([2, 4, 4, 4], $this->getTableSizesFor($booking2));
    }

    public function test_it_throws_an_exception_if_not_enough_seats_are_available_for_the_guests_requested()
    {
        $this->expectException(\Exception::class);

        //Act
        $this->makeBooking(['people' => 100]);

        //Assert
        $this->assertDatabaseCount('bookings', 0);
    }
}
