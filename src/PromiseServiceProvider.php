<?php

namespace Tochka\Promises;

use Illuminate\Support\ServiceProvider;

class PromiseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Tochka\Promises\Facades\Promise::class, static function () {
            return new PromiseExecutor();
        });
    }
}
