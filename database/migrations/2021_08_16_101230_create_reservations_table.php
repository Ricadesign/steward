<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->integer('adults');
            $table->integer('childs');
            $table->date('reservation_date');
            $table->string('hour');
            $table->enum('status', ['pending', 'comfirmed'])->default('pending');
            $table->enum('shift', ['midday', 'night']);
            $table->string('name');
            $table->string('phone');
            $table->string('email');
            $table->string('observations')->nullable();
            $table->bigInteger('table_id')->nullable();
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
        Schema::dropIfExists('reservations');
    }
}
