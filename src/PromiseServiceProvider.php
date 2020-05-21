<?php

namespace Tochka\Promises;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Tochka\Promises\Commands\PromiseMakeMigration;
use Tochka\Promises\Commands\PromiseWatch;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Contracts\PromisedEvent;
use Tochka\Promises\Core\Dispatchers\PromiseDispatcher;
use Tochka\Promises\Core\Dispatchers\QueueJobDispatcher;
use Tochka\Promises\Core\Dispatchers\WaitEventDispatcher;
use Tochka\Promises\Core\PromiseWatcher;
use Tochka\Promises\Core\Support\BaseJobDispatcher;
use Tochka\Promises\Core\Support\EventDispatcher;
use Tochka\Promises\Core\Support\QueuePromiseMiddleware;
use Tochka\Promises\Core\Support\Serializer;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\PromiseJobStateChanged;
use Tochka\Promises\Events\PromiseStateChanged;
use Tochka\Promises\Events\StateChanged;
use Tochka\Promises\Listeners\DispatchPromise;
use Tochka\Promises\Listeners\DispatchPromiseJob;
use Tochka\Promises\Listeners\FilterStateChanged;
use Tochka\Promises\Listeners\LogStateChanged;
use Tochka\Promises\Registry\PromiseEventRegistry;
use Tochka\Promises\Registry\PromiseJobRegistry;
use Tochka\Promises\Registry\PromiseRegistry;

class PromiseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PromiseWatch::class,
                PromiseMakeMigration::class,
            ]);

            // публикуем конфигурации
            $this->publishes(
                [__DIR__ . '/../config/promises.php' => $this->app->basePath() . '/config/promises.php'],
                'promises-config');
        }

        Bus::pipeThrough([QueuePromiseMiddleware::class]);

        Queue::createPayloadUsing(static function ($connection, $queue, $payload) {
            $job = $payload['data']['command'] ?? $payload['job'] ?? null;

            if ($job instanceof MayPromised && $job->getBaseJobId() !== null) {
                return [
                    'promised'    => true,
                    'base_job_id' => $job->getBaseJobId(),
                ];
            }

            return [];
        });

        Queue::failing(static function (JobFailed $event) {
            $base_job_id = $event->job->payload()['base_job_id'] ?? null;
            if ($base_job_id === null) {
                return;
            }

            $baseJob = Facades\PromiseJobRegistry::load($base_job_id);
            $baseJob->setException($event->exception);
            $baseJob->setState(StateEnum::FAILED());
            Facades\PromiseJobRegistry::save($baseJob);
        });

        Queue::after(static function (JobProcessed $event) {
            $base_job_id = $event->job->payload()['base_job_id'] ?? null;

            if ($base_job_id === null || $event->job->isReleased()) {
                return;
            }

            $state = StateEnum::SUCCESS();
            if ($event->job->hasFailed()) {
                $state = StateEnum::FAILED();
            }

            $baseJob = Facades\PromiseJobRegistry::load($base_job_id);

            if ($baseJob->getState()->in([StateEnum::WAITING(), StateEnum::RUNNING()])) {
                $baseJob->setState($state);
                Facades\PromiseJobRegistry::save($baseJob);
            }
        });

        Event::listen(PromisedEvent::class, static function (PromisedEvent $event) {
            Facades\EventDispatcher::dispatch($event);
        });

        Event::listen(StateChanged::class, FilterStateChanged::class);
        Event::listen(StateChanged::class, LogStateChanged::class);
        Event::listen(PromiseStateChanged::class, DispatchPromise::class);
        Event::listen(PromiseJobStateChanged::class, DispatchPromiseJob::class);
    }

    public function register(): void
    {
        $this->app->singleton(Facades\BaseJobDispatcher::class, static function () {
            $dispatcher = new BaseJobDispatcher();
            $dispatcher->addDispatcher(new WaitEventDispatcher());
            $dispatcher->addDispatcher(new QueueJobDispatcher());
            $dispatcher->addDispatcher(new PromiseDispatcher());

            return $dispatcher;
        });

        $this->app->singleton(Facades\Serializer::class, static function () {
            return new Serializer();
        });

        $this->app->singleton(Facades\EventDispatcher::class, static function () {
            return new EventDispatcher();
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

        $this->app->singleton(Facades\PromiseEventRegistry::class, static function () {
            return new PromiseEventRegistry();
        });
    }
}
