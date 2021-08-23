<?php

namespace Ricadesign\Steward\Tests;

use Faker\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Ricadesign\Steward\Table;

class BookingTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_simple_booking()
    {
        //Arrange
        Table::factory()->count(5)->create();
        
        $this->assertCount(5, Table::all());
        $this->assertTrue(true);
    }
}
