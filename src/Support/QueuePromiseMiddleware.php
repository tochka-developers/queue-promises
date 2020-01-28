<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\BaseJob;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Contracts\States;

class QueuePromiseMiddleware
{
    /** @var \Tochka\Promises\BaseJob */
    private $baseJob;

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
            $this->baseJob->save();
            throw $e;
        }

        $this->baseJob->setResult($job);
        $this->baseJob->setState(States::SUCCESS);
        $this->baseJob->save();
    }
}
