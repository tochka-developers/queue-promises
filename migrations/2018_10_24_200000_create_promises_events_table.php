<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromisesEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('promises.database.events_table', 'promise_events'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('promise_id')->nullable();
            $table->string('event_name');
            $table->string('event_id')->nullable();
            $table->timestamps();

            $table->index(['event_name', 'event_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('promises.database.events_table', 'promise_events'));
    }
}
