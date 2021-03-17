<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePromisesTestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'promises',
            function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('state');
                $table->longText('conditions');
                $table->longText('promise_handler');
                $table->timestamp('watch_at');
                $table->timestamp('timeout_at');
                $table->timestamps();

                $table->index('state');
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
        Schema::dropIfExists('{{table}}');
    }
}
