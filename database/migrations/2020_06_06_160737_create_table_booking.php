<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableBooking extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('booking', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('passengers');
            $table->timestamp('pickup_time');
            $table->string('booking_type');
            $table->text('note');
            $table->string('origin');
            $table->string('destination');
            $table->string('fare');
            $table->string('by')->default("nouser");
            $table->integer('status');
            $table->string('booking_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('booking');
    }
}
