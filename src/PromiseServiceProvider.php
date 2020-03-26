<?php

namespace Tochka\Promises;

use Illuminate\Support\ServiceProvider;
use Tochka\Promises\Registry\PromiseJobRegistry;
use Tochka\Promises\Registry\PromiseRegistry;

class PromiseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Tochka\Promises\Facades\Promise::class, static function () {
            return new PromiseExecutor();
        });

        $this->app->singleton(\Tochka\Promises\Facades\PromiseRegistry::class, static function () {
            return new PromiseRegistry();
        });

        $this->app->singleton(\Tochka\Promises\Facades\PromiseJobRegistry::class, static function () {
            return new PromiseJobRegistry();
        });
    }
}
