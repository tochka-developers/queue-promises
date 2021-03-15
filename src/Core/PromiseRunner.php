<?php

namespace Tochka\Promises\Core;

use Tochka\Promises\Contracts\PromiseHandler;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseJob;

class PromiseRunner
{
    /**
     * @param PromiseHandler                                $handler
     * @param array<\Tochka\Promises\Contracts\MayPromised> $jobs
     */
    public function run(PromiseHandler $handler, array $jobs): void
    {
        $basePromise = new BasePromise($handler);

        $traits = class_uses_recursive($handler);

        foreach ($traits as $trait) {
            if (method_exists($handler, $method = 'promiseConditions' . class_basename($trait))) {
                $handler->$method($basePromise);
            }
        }

        Promise::saveBasePromise($basePromise);

        foreach ($jobs as $job) {
            $baseJob = new BaseJob($basePromise->getPromiseId(), $job);
            PromiseJob::saveBaseJob($baseJob);

            $job->setBaseJobId($baseJob->getJobId());
            $baseJob->setInitial($job);

            foreach ($traits as $trait) {
                if (method_exists($handler, $method = 'jobConditions' . class_basename($trait))) {
                    $handler->$method($basePromise, $baseJob);
                }
            }

            PromiseJob::saveBaseJob($baseJob);
        }

        foreach ($traits as $trait) {
            if (method_exists($handler, $method = 'afterRun' . class_basename($trait))) {
                $handler->$method();
            }
        }

        $basePromise->dispatch();
    }
}
