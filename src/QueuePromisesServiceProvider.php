<?php

namespace Tochka\Queue\Promises;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Tochka\Queue\Promises\Console\PromiseMakeCommand;
use Tochka\Queue\Promises\Contracts\MayPromised;
use Tochka\Queue\Promises\Jobs\Promise;

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
    }

    protected function processQueueEvents()
    {
        Queue::after(function (JobProcessed $event) {
            try {
                $job = app('QueueCurrentJob');
            } catch (\Exception $e) {
                return true;
            }

            if (!($job instanceof MayPromised) || !$job->hasResult()) {
                return true;
            }

            $job->setJobStatus(MayPromised::JOB_STATUS_SUCCESS);

            try {
                Promise::checkPromise($job);
            } catch (\Exception $e) {
                echo $e->getMessage() . "\n";
            }

            return true;
        });

        Queue::failing(function (JobFailed $event) {
            try {
                $job = app('QueueCurrentJob');
            } catch (\Exception $e) {
                return true;
            }

            if (!($job instanceof MayPromised) || !$job->hasResult()) {
                return true;
            }

            $job->setJobStatus(MayPromised::JOB_STATUS_ERROR);
            $error = [
                'code'    => $event->exception->getCode(),
                'message' => $event->exception->getMessage(),
                'trace'   => $event->exception->getTraceAsString(),
            ];
            $job->setJobErrors([$error]);

            try {
                Promise::checkPromise($job);
            } catch (\Exception $e) {
                echo $e->getMessage() . "\n";
            }

            return true;
        });
    }
}
