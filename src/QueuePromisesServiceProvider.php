<?php

namespace Tochka\Queue\Promises;

use Illuminate\Foundation\Console\PromiseMakeCommand;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
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
            $job = $this->getJobFromPayload($event);

            if (!($job instanceof MayPromised) ||!$job->hasResult()) {
                return true;
            }

            $job->setJobStatus(MayPromised::JOB_STATUS_SUCCESS);

            Promise::checkPromise($job);

            return true;
        });

        Queue::failing(function (JobFailed $event) {
            $job = $this->getJobFromPayload($event);

            if (!($job instanceof MayPromised) || !$job->hasResult()) {
                return true;
            }

            $job->setJobStatus(MayPromised::JOB_STATUS_ERROR);
            $job->setJobErrors([$event->exception]);

            Promise::checkPromise($job);

            return true;
        });
    }

    private function getJobFromPayload($event)
    {
        $payload = $event->job->payload();

        if (!empty($payload['data']['command'])) {
            return unserialize($payload['data']['command']);
        }

        return null;
    }

}
