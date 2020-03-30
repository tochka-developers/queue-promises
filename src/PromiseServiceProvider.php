<?php

namespace Tochka\Promises;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\ServiceProvider;
use Tochka\Promises\Commands\PromiseWatch;
use Tochka\Promises\Core\Dispatchers\PromiseDispatcher;
use Tochka\Promises\Core\Dispatchers\QueueJobDispatcher;
use Tochka\Promises\Core\PromiseWatcher;
use Tochka\Promises\Core\Support\BaseJobDispatcher;
use Tochka\Promises\Core\Support\QueuePromiseMiddleware;
use Tochka\Promises\Core\Support\Serializer;
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

        Bus::pipeThrough([QueuePromiseMiddleware::class]);
    }

    public function register(): void
    {
        $this->app->singleton(Facades\BaseJobDispatcher::class, static function () {
            $dispatcher = new BaseJobDispatcher();
            $dispatcher->addDispatcher(new QueueJobDispatcher());
            $dispatcher->addDispatcher(new PromiseDispatcher());

            return $dispatcher;
        });

        $this->app->singleton(Facades\Serializer::class, static function () {
            return new Serializer();
        });

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
