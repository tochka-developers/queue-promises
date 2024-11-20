<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
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
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('promise_events');
    }
};
