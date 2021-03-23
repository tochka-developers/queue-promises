<?php

namespace Tochka\Promises\Core\Support;

use Tochka\Promises\Contracts\JobFacadeContract;
use Tochka\Promises\Contracts\JobStateContract;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\PromiseJob;

class QueuePromiseMiddleware
{
    /**
     * @param $queueJob
     * @param $next
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle($queueJob, $next)
    {
        if (!$queueJob instanceof MayPromised || $queueJob->getBaseJobId() === null) {
            return $next($queueJob);
        }

        try {
            return $next($queueJob);
        } finally {
            $jobModel = PromiseJob::find($queueJob->getBaseJobId());
            if ($jobModel !== null) {
                $this->setJobStateAndResult($queueJob, $jobModel->getBaseJob());
            }
        }
    }

    public function setJobStateAndResult(MayPromised $queueJob, BaseJob $baseJob): void
    {
        // меняем состояние только если задача находится в состоянии ожидание или запущена
        if ($queueJob instanceof JobStateContract && $baseJob->getState()->in(
                [
                    StateEnum::WAITING(),
                    StateEnum::RUNNING(),
                ]
            )) {
            $baseJob->setState($queueJob->getState());
        }

        if ($queueJob instanceof JobFacadeContract) {
            $baseJob->setResult($queueJob->getJobHandler());
        } else {
            $baseJob->setResult($queueJob);
        }

        PromiseJob::saveBaseJob($baseJob);
    }
}
