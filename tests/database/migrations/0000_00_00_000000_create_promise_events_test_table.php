<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePromiseEventsTestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'promise_events',
            function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('job_id');
                $table->string('event_name');
                $table->string('event_unique_id');
                $table->timestamps();

                $table->index(['event_name', 'event_unique_id']);
                $table->index('job_id');
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promise_events');
    }
}
