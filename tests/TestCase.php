<?php

namespace Ricadesign\Steward\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Illuminate\Foundation\Application;
use Ricadesign\Steward\Table;

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
