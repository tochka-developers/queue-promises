<?php

namespace Tochka\Promises;

use Illuminate\Support\ServiceProvider;
use Tochka\Promises\Commands\PromiseWatch;
use Tochka\Promises\Core\PromiseWatcher;
use Tochka\Promises\Registry\PromiseJobRegistry;
use Tochka\Promises\Registry\PromiseRegistry;

class PromiseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PromiseWatch::class,
            ]);

            // публикуем конфигурации
            $this->publishes(
                [__DIR__ . '/../config/promises.php' => $this->app->basePath() . '/config/promises.php'],
                'promises-config');
        }
    }

    public function register(): void
    {
        $this->app->singleton(Facades\PromiseWatcher::class, static function () {
            return new PromiseWatcher();
        });

        $this->app->singleton(Facades\PromiseRegistry::class, static function () {
            return new PromiseRegistry();
        });

        $this->app->singleton(Facades\PromiseJobRegistry::class, static function () {
            return new PromiseJobRegistry();
        });
    }
}
