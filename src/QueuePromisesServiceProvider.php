<?php

namespace Tochka\Queue\Promises;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Tochka\Queue\Promises\Console\PromiseMakeCommand;
use Tochka\Queue\Promises\Contracts\MayPromised;
use Tochka\Queue\Promises\Contracts\PromisedEvent;
use Tochka\Queue\Promises\Jobs\Promise;
use Tochka\Queue\Promises\Jobs\WaitEvent;

/**
 * Описание QueuePromisesServiceProvider
 */
class QueuePromisesServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PromiseMakeCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__ . '/../config/promises.php' => config_path('promises.php'),
        ], 'config');

        $this->loadMigrationsFrom(__DIR__ . '/../migrations');

        $this->processQueueEvents();
        $this->processWaitingEvents();
    }

    protected function processQueueEvents()
    {
        Queue::after(function (JobProcessed $event) {
            if ($event->job->isReleased()) {
                return true;
            }
            return $this->processQueueEvent();
        });

        Queue::failing(function (JobFailed $event) {
            return $this->processQueueEvent($event->exception);
        });
    }

    protected function processWaitingEvents()
    {
        // перехватываем все события, вдруг на какое-то подписан промис
        Event::listen('*', function ($eventName, $payload) {
            if ($this->promisedEvent($payload)) {
                $jobs = WaitEvent::resolve($payload[0]);
                if (empty($jobs)) {
                    return;
                }

                foreach ($jobs as $job) {
                    $job->setJobStatus(MayPromised::JOB_STATUS_SUCCESS);

                    try {
                        Promise::checkPromise($job);
                    } catch (\Exception $e) {
                        echo $e->getMessage() . "\n";
                    }

                    $job->flush();
                }
            }
        });
    }

    /**
     * @param null $exception
     *
     * @return bool
     */
    protected function processQueueEvent($exception = null): bool
    {
        try {
            $job = app('QueueCurrentJob');
        } catch (\Exception $e) {
            return true;
        }

        if (!($job instanceof MayPromised) || !$job->hasResult()) {
            return true;
        }

        if ($job->hasFailed()) {
            $job->setJobStatus(MayPromised::JOB_STATUS_ERROR);
            if ($exception !== null) {
                $job->errorHandle($exception);
            }
        } else {
            $job->setJobStatus(MayPromised::JOB_STATUS_SUCCESS);
        }

        try {
            Promise::checkPromise($job);
        } catch (\Exception $e) {
            Log::channel(config('promises.log_channel', 'default'))
                ->error('Error while check promise; ' . $e->getMessage());
        }

        return true;
    }

    protected function promisedEvent($payload)
    {
        return isset($payload[0]) && $payload[0] instanceof PromisedEvent;
    }
}
