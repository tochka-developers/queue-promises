<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\BaseJob;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Contracts\States;
use Tochka\Promises\Facades\PromiseJobRegistry;

class QueuePromiseMiddleware
{
    /** @var \Tochka\Promises\BaseJob */
    private BaseJob $baseJob;

    public function __construct(BaseJob $baseJob)
    {
        $this->baseJob = $baseJob;
    }

    /**
     * @param MayPromised $job
     * @param             $next
     *
     * @throws \Exception
     */
    public function handle(MayPromised $job, $next): void
    {
        try {
            $next($job);
        } catch (\Exception $e) {
            $this->baseJob->setResult($job);
            PromiseJobRegistry::save($this->baseJob);
            throw $e;
        }

        $this->baseJob->setResult($job);
        $this->baseJob->setState(States::SUCCESS);
        PromiseJobRegistry::save($this->baseJob);
    }
}
