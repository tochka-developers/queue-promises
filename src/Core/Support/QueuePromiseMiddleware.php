<?php

namespace Tochka\Promises\Core\Support;

use Tochka\Promises\Contracts\JobFacadeContract;
use Tochka\Promises\Contracts\JobStateContract;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\PromiseJob;

class QueuePromiseMiddleware
{
    /**
     * @param $job
     * @param $next
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle($job, $next)
    {
        if (!$job instanceof MayPromised || $job->getBaseJobId() === null) {
            return $next($job);
        }

        try {
            return $next($job);
        } finally {
            $jobModel = PromiseJob::find($job->getBaseJobId());
            if ($jobModel !== null) {
                $baseJob = $jobModel->getBaseJob();

                // меняем состояние только если задача находится в состоянии ожидание или запущена
                if ($job instanceof JobStateContract && $baseJob->getState()->in(
                        [
                            StateEnum::WAITING(),
                            StateEnum::RUNNING(),
                        ]
                    )) {
                    $baseJob->setState($job->getState());
                }

                if ($job instanceof JobFacadeContract) {
                    $baseJob->setResult($job->getJobHandler());
                } else {
                    $baseJob->setResult($job);
                }

                PromiseJob::saveBaseJob($baseJob);
            }
        }
    }
}
