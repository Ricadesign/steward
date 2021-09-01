<?php

namespace Ricadesign\Steward\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Ricadesign\Steward\Table;

class TableFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Table::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'size' => 4
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterCreating(function (Table $table) {
            $table->num = $table->id;
            $table->save();
        });
    }
}
