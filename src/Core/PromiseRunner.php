<?php

namespace Tochka\Promises\Core;

use Tochka\Promises\Contracts\PromiseHandler;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseJob;

class PromiseRunner
{
    /** @var array<string, array> */
    private array $traits = [];

    /**
     * @param PromiseHandler                                $handler
     * @param array<\Tochka\Promises\Contracts\MayPromised> $jobs
     */
    public function run(PromiseHandler $handler, array $jobs): void
    {
        $basePromise = new BasePromise($handler);

        $this->hookTraitsMethod($handler, 'promiseConditions', $basePromise);

        Promise::saveBasePromise($basePromise);

        foreach ($jobs as $job) {
            $baseJob = new BaseJob($basePromise->getPromiseId(), $job);
            PromiseJob::saveBaseJob($baseJob);

            $job->setBaseJobId($baseJob->getJobId());
            $baseJob->setInitial($job);

            $this->hookTraitsMethod($handler, 'jobConditions', $basePromise, $baseJob);

            PromiseJob::saveBaseJob($baseJob);
        }

        $this->hookTraitsMethod($handler, 'afterRun');

        $basePromise->dispatch();
    }

    public function hookTraitsMethod(PromiseHandler $handler, string $methodName, ...$args): void
    {
        $loadedTraits = $this->getHandlerTraits($handler);

        foreach ($loadedTraits as $trait) {
            if (method_exists($handler, $method = $methodName . class_basename($trait))) {
                $handler->$method(...$args);
            }
        }
    }

    public function getHandlerTraits(PromiseHandler $handler): array
    {
        $key = get_class($handler);

        if (!array_key_exists($key, $this->traits)) {
            $this->traits[$key] = class_uses_recursive($handler);
        }

        return $this->traits[$key];
    }
}
