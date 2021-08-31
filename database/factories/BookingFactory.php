<?php

namespace Ricadesign\Steward\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Ricadesign\Steward\Booking;
use Carbon\Carbon;

class BookingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Booking::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'people' => 2,
            'reservation_at' => Carbon::createFromTime(22),
            'shift' => 'night',
            'name' => 'John',
            'phone' => '555-555-555',
            'email' => 'john@test.com',
        ];
    }
}
