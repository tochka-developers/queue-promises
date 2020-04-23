<?php

namespace Tochka\Promises\Core\Support;

use Tochka\Promises\Contracts\JobFacadeContract;
use Tochka\Promises\Contracts\JobStateContract;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Facades\PromiseJobRegistry;

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

        $baseJob = PromiseJobRegistry::load($job->getBaseJobId());

        try {
            $result = $next($job);

            if ($job instanceof JobStateContract) {
                $baseJob->setState($job->getState());
            } else {
                $baseJob->setState(StateEnum::SUCCESS());
            }

            return $result;
        } finally {
            if ($job instanceof JobFacadeContract) {
                $baseJob->setResult($job->getJobHandler());
            } else {
                $baseJob->setResult($job);
            }

            PromiseJobRegistry::save($baseJob);
        }
    }
}
