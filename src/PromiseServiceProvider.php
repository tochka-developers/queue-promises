<?php

namespace Tochka\Promises;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Tochka\Promises\Commands\PromiseGcc;
use Tochka\Promises\Commands\PromiseMakeMigration;
use Tochka\Promises\Commands\PromiseWatch;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Contracts\PromisedEvent;
use Tochka\Promises\Core\Dispatchers\PromiseDispatcher;
use Tochka\Promises\Core\Dispatchers\QueueJobDispatcher;
use Tochka\Promises\Core\Dispatchers\WaitEventDispatcher;
use Tochka\Promises\Core\GarbageCollector;
use Tochka\Promises\Core\PromiseRunner;
use Tochka\Promises\Core\PromiseWatcher;
use Tochka\Promises\Core\Support\BaseJobDispatcher;
use Tochka\Promises\Core\Support\EventDispatcher;
use Tochka\Promises\Core\Support\QueuePromiseMiddleware;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\PromiseJobStateChanged;
use Tochka\Promises\Events\PromiseStateChanged;
use Tochka\Promises\Events\StateChanged;
use Tochka\Promises\Listeners\CheckPromiseConditions;
use Tochka\Promises\Listeners\CheckPromiseJobConditions;
use Tochka\Promises\Listeners\DispatchPromise;
use Tochka\Promises\Listeners\DispatchPromiseJob;
use Tochka\Promises\Listeners\LogStateChanged;
use Tochka\Promises\Models\PromiseJob;
use Tochka\Promises\Registry\PromiseEventRegistry;
use Tochka\Promises\Registry\PromiseJobRegistry;
use Tochka\Promises\Registry\PromiseRegistry;

class PromiseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands(
                [
                    PromiseWatch::class,
                    PromiseGcc::class,
                    PromiseMakeMigration::class,
                ]
            );

            // публикуем конфигурации
            $this->publishes(
                [__DIR__ . '/../config/promises.php' => $this->app->basePath() . '/config/promises.php'],
                'promises-config'
            );
        }

        Bus::pipeThrough([QueuePromiseMiddleware::class]);

        Queue::createPayloadUsing(
            static function ($connection, $queue, $payload) {
                $job = $payload['data']['command'] ?? $payload['job'] ?? null;

                if ($job instanceof MayPromised && $job->getBaseJobId() !== null) {
                    return [
                        'promised'    => true,
                        'base_job_id' => $job->getBaseJobId(),
                    ];
                }

                return [];
            }
        );

        Queue::failing(
            static function (JobFailed $event) {
                $baseJobId = $event->job->payload()['base_job_id'] ?? null;
                if ($baseJobId === null) {
                    return;
                }

                $job = PromiseJob::find($baseJobId);
                if ($job === null) {
                    return;
                }
                $baseJob = $job->getBaseJob();
                $baseJob->setException($event->exception);
                $baseJob->setState(StateEnum::FAILED());
                PromiseJob::saveBaseJob($baseJob);
            }
        );

        Queue::after(
            static function (JobProcessed $event) {
                $baseJobId = $event->job->payload()['base_job_id'] ?? null;

                if ($baseJobId === null || $event->job->isReleased()) {
                    return;
                }

                $state = StateEnum::SUCCESS();
                if ($event->job->hasFailed()) {
                    $state = StateEnum::FAILED();
                }

                $job = PromiseJob::find($baseJobId);
                if ($job === null) {
                    return;
                }

                $baseJob = $job->getBaseJob();

                if ($baseJob->getState()->in([StateEnum::WAITING(), StateEnum::RUNNING()])) {
                    $baseJob->setState($state);
                    PromiseJob::saveBaseJob($baseJob);
                }
            }
        );

        Event::listen(
            PromisedEvent::class,
            static function (PromisedEvent $event) {
                Facades\EventDispatcher::dispatch($event);
            }
        );

        Event::listen(StateChanged::class, LogStateChanged::class);
        Event::listen(PromiseStateChanged::class, DispatchPromise::class);
        Event::listen(PromiseJobStateChanged::class, DispatchPromiseJob::class);

        $watchUpdates = Config::get('promises.fire_updates', false);
        if ($watchUpdates) {
            Event::listen(PromiseStateChanged::class, CheckPromiseJobConditions::class);
            Event::listen(PromiseJobStateChanged::class, CheckPromiseJobConditions::class);
            Event::listen(PromiseJobStateChanged::class, CheckPromiseConditions::class);
        }
    }

    public function register(): void
    {
        if (Config::get('promises.fire_updates', false)) {
            $this->app->instance(
                'watcher_watch_timeout',
                Config::get('promises.watcher_watch_timeout', false)
            );
        } else {
            $this->app->instance('watcher_watch_timeout', 0);
        }

        $this->app->singleton(
            Facades\BaseJobDispatcher::class,
            static function () {
                $dispatcher = new BaseJobDispatcher();
                $dispatcher->addDispatcher(new WaitEventDispatcher());
                $dispatcher->addDispatcher(new QueueJobDispatcher());
                $dispatcher->addDispatcher(new PromiseDispatcher());

                return $dispatcher;
            }
        );

        $this->app->singleton(
            Facades\EventDispatcher::class,
            static function () {
                return new EventDispatcher();
            }
        );

        $this->app->singleton(
            Facades\Promises::class,
            static function () {
                return new PromiseRunner();
            }
        );

        $this->app->singleton(
            Facades\PromiseWatcher::class,
            static function () {
                return new PromiseWatcher();
            }
        );

        $this->app->singleton(
            Facades\GarbageCollector::class,
            static function () {
                return new GarbageCollector();
            }
        );

        $this->app->singleton(
            Facades\PromiseRegistry::class,
            static function () {
                return new PromiseRegistry();
            }
        );

        $this->app->singleton(
            Facades\PromiseJobRegistry::class,
            static function () {
                return new PromiseJobRegistry();
            }
        );

        $this->app->singleton(
            Facades\PromiseEventRegistry::class,
            static function () {
                return new PromiseEventRegistry();
            }
        );
    }
}
