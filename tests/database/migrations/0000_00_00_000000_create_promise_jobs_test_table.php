<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
    public function up(): void
    {
        Schema::create(
            'promise_jobs',
            function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('promise_id');
                $table->string('state');
                $table->longText('conditions');
                $table->longText('initial_job');
                $table->longText('result_job');
                $table->longText('exception')->nullable();
                $table->timestamps();

                $table->index('promise_id');
                $table->index('state');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('promise_jobs');
    }
};
