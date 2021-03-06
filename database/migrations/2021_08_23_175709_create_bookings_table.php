<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->integer('people');
            $table->datetime('reservation_at');
            $table->enum('status', ['pending', 'confirmed'])->default('pending');
            $table->enum('shift', ['midday', 'night']);
            $table->string('name');
            $table->string('phone');
            $table->string('email');
            $table->string('observations')->nullable();
            $table->string('locale')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bookings');
    }
}
