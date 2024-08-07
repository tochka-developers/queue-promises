<?php

namespace Tochka\Promises;

use Illuminate\Contracts\Container\Container;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Tochka\Promises\Commands\PromiseClean;
use Tochka\Promises\Commands\PromiseGc;
use Tochka\Promises\Commands\PromiseMakeMigration;
use Tochka\Promises\Commands\PromiseWatch;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Contracts\PromisedEvent;
use Tochka\Promises\Core\Dispatchers\PromiseDispatcher;
use Tochka\Promises\Core\Dispatchers\QueueJobDispatcher;
use Tochka\Promises\Core\Dispatchers\WaitEventDispatcher;
use Tochka\Promises\Core\GarbageCollector;
use Tochka\Promises\Core\GarbageCollectorInterface;
use Tochka\Promises\Core\PromiseRunner;
use Tochka\Promises\Core\PromiseRunnerInterface;
use Tochka\Promises\Core\PromiseWatcher;
use Tochka\Promises\Core\PromiseWatcherInterface;
use Tochka\Promises\Core\Support\BaseJobDispatcher;
use Tochka\Promises\Core\Support\BaseJobDispatcherInterface;
use Tochka\Promises\Core\Support\ConditionTransitionHandler;
use Tochka\Promises\Core\Support\ConditionTransitionHandlerInterface;
use Tochka\Promises\Core\Support\EventDispatcher;
use Tochka\Promises\Core\Support\EventDispatcherInterface;
use Tochka\Promises\Core\Support\QueuePromiseMiddleware;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\PromiseJobStateChanged;
use Tochka\Promises\Events\PromiseStateChanged;
use Tochka\Promises\Events\StateChanged;
use Tochka\Promises\Listeners\CheckStateConditions;
use Tochka\Promises\Listeners\DeletePromisedEvent;
use Tochka\Promises\Listeners\DispatchPromise;
use Tochka\Promises\Listeners\DispatchPromiseJob;
use Tochka\Promises\Listeners\LogStateChanged;
use Tochka\Promises\Models\Observers\PromiseAfterCommitObserver;
use Tochka\Promises\Models\Observers\PromiseBeforeCommitObserver;
use Tochka\Promises\Models\Observers\PromiseJobAfterCommitObserver;
use Tochka\Promises\Models\Observers\PromiseJobBeforeCommitObserver;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseJob;
use Tochka\Promises\Registry\PromiseEventRegistry;
use Tochka\Promises\Registry\PromiseEventRegistryInterface;
use Tochka\Promises\Registry\PromiseJobRegistry;
use Tochka\Promises\Registry\PromiseJobRegistryInterface;
use Tochka\Promises\Registry\PromiseRegistry;
use Tochka\Promises\Registry\PromiseRegistryInterface;

/**
 * @api
 */
class PromiseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands(
                [
                    PromiseWatch::class,
                    PromiseGc::class,
                    PromiseClean::class,
                    PromiseMakeMigration::class,
                ],
            );

            // публикуем конфигурации
            $this->publishes(
                [__DIR__ . '/../config/promises.php' => $this->app->basePath() . '/config/promises.php'],
                'promises-config',
            );
        }

        Bus::pipeThrough([QueuePromiseMiddleware::class]);

        Queue::createPayloadUsing(
            /** @psalm-suppress MissingClosureParamType */
            static function ($connection, $queue, $payload) {
                $job = $payload['data']['command'] ?? $payload['job'] ?? null;

                if ($job instanceof MayPromised && $job->getBaseJobId() !== null) {
                    return [
                        'promised' => true,
                        'base_job_id' => $job->getBaseJobId(),
                    ];
                }

                return [];
            },
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

                if ($baseJob->getState()->in([StateEnum::WAITING(), StateEnum::RUNNING()])) {
                    $baseJob->setException($event->exception);
                    $baseJob->setState(StateEnum::FAILED());
                    PromiseJob::saveBaseJob($baseJob);
                }
            },
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
            },
        );

        Promise::observe(PromiseBeforeCommitObserver::class);
        Promise::observe(PromiseAfterCommitObserver::class);
        PromiseJob::observe(PromiseJobBeforeCommitObserver::class);
        PromiseJob::observe(PromiseJobAfterCommitObserver::class);

        Event::listen(
            PromisedEvent::class,
            static function (PromisedEvent $event) {
                /** @var EventDispatcherInterface $eventDispatcher */
                $eventDispatcher = App::make(EventDispatcherInterface::class);
                $eventDispatcher->dispatch($event);
            },
        );

        Event::listen(StateChanged::class, LogStateChanged::class);

        $watchUpdates = Config::get('promises.fire_updates', false);
        if ($watchUpdates) {
            Event::listen(PromiseStateChanged::class, CheckStateConditions::class);
            Event::listen(PromiseJobStateChanged::class, CheckStateConditions::class);
        }

        Event::listen(PromiseStateChanged::class, DispatchPromise::class);
        Event::listen(PromiseJobStateChanged::class, DispatchPromiseJob::class);
        Event::listen(PromiseJobStateChanged::class, DeletePromisedEvent::class);
    }

    public function register(): void
    {
        if (Config::get('promises.fire_updates', false)) {
            $this->app->instance(
                'watcher_watch_timeout',
                Config::get('promises.watcher_watch_timeout', 60 * 10),
            );
        } else {
            $this->app->instance('watcher_watch_timeout', 0);
        }

        $this->app->singleton(
            BaseJobDispatcherInterface::class,
            static function (): BaseJobDispatcherInterface {
                $dispatcher = new BaseJobDispatcher();
                $dispatcher->addDispatcher(new WaitEventDispatcher());
                $dispatcher->addDispatcher(new QueueJobDispatcher());
                $dispatcher->addDispatcher(new PromiseDispatcher());

                return $dispatcher;
            },
        );

        $this->app->singleton(EventDispatcherInterface::class, EventDispatcher::class);
        $this->app->singleton(PromiseRunnerInterface::class, PromiseRunner::class);
        $this->app->singleton(ConditionTransitionHandlerInterface::class, ConditionTransitionHandler::class);
        $this->app->singleton(PromiseEventRegistryInterface::class, PromiseEventRegistry::class);
        $this->app->singleton(PromiseJobRegistryInterface::class, PromiseJobRegistry::class);
        $this->app->singleton(PromiseRegistryInterface::class, PromiseRegistry::class);

        $this->app->singleton(
            PromiseWatcherInterface::class,
            static function (Container $container): PromiseWatcherInterface {
                return $container->make(PromiseWatcher::class, [
                    'sleepTime' => Config::get('promises.watcher_sleep', 60 * 10),
                    'promisesTable' => Config::get('promises.database.table_promises', 'promises'),
                    'promiseJobsTable' => Config::get('promises.database.table_jobs', 'promise_jobs'),
                    'promiseChunkSize' => Config::get('promises.garbage_collector.promise_chunk_size', 100),
                ]);
            },
        );

        $this->app->singleton(
            GarbageCollectorInterface::class,
            static function (): GarbageCollectorInterface {
                $sleepTime = Config::get('promises.garbage_collector.timeout', 60 * 10);
                $deleteOlderThen = Config::get('promises.garbage_collector.older_then', 60 * 60 * 24 * 7);
                $states = Config::get('promises.garbage_collector.states', []);
                $promisesTable = Config::get('promises.database.table_promises', 'promises');
                $promiseJobsTable = Config::get('promises.database.table_jobs', 'promise_jobs');
                $promiseEventsTable = Config::get('promises.database.table_events', 'promise_events');
                $promiseChunkSize = Config::get('promises.garbage_collector.promise_chunk_size', 100);
                $jobsChunkSize = Config::get('promises.garbage_collector.jobs_chunk_size', 500);

                return new GarbageCollector(
                    $sleepTime,
                    $deleteOlderThen,
                    $states,
                    $promisesTable,
                    $promiseJobsTable,
                    $promiseEventsTable,
                    $promiseChunkSize,
                    $jobsChunkSize,
                );
            },
        );
    }
}
