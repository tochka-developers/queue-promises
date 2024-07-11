<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create(
            'promises',
            function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('parent_job_id')->nullable();
                $table->string('state');
                $table->longText('conditions');
                $table->longText('promise_handler');
                $table->timestamp('watch_at')->nullable();
                $table->timestamp('timeout_at')->nullable();
                $table->timestamps();

                $table->index(['state', 'updated_at']);
                $table->index(['state', 'watch_at']);
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('promises');
    }
};
