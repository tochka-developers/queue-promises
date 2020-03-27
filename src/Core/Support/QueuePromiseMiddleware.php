<?php

namespace Tochka\Promises\Core\Support;

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
            $baseJob->setState(StateEnum::SUCCESS());

            return $result;
        } finally {
            $baseJob->setResult($job);
            PromiseJobRegistry::save($baseJob);
        }
    }
}
