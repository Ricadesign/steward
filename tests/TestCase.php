<?php

namespace Ricadesign\Steward\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Ricadesign\Steward\Table;
use Illuminate\Support\Facades\Artisan;

/**
 * Override the standard PHPUnit testcase with the Testbench testcase
 *
 * @see https://github.com/orchestral/testbench#usage
 */
abstract class TestCase extends OrchestraTestCase
{
    /**
     * Include the package's service provider(s)
     *
     * @see https://github.com/orchestral/testbench#custom-service-provider
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            \Ricadesign\Steward\Providers\StewardServiceProvider::class
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Import the migrations
        include_once str_replace('\\', '/', __DIR__ . '/../database/migrations/2021_08_23_155531_create_tables_table.php');
        include_once str_replace('\\', '/', __DIR__ . '/../database/migrations/2021_08_23_175709_create_bookings_table.php');
        include_once str_replace('\\', '/', __DIR__ . '/../database/migrations/2021_08_23_182159_create_booking_table_table.php');

        // Run them
        (new \CreateTablesTable)->up();
        (new \CreateBookingsTable)->up();
        (new \CreateBookingTableTable)->up();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Tables count: 11
        // Total capacity: 44
        Table::factory()->count(3)->create([
            'size' => 2
        ]);
        Table::factory(6)->create();
        Table::factory(1)->create([
            'size' => 6
        ]);
        Table::factory(1)->create([
            'size' => 8
        ]);
    }
}
