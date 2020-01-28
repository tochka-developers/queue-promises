<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\BasePromise;
use Tochka\Promises\Contracts\DefaultTransitions;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Contracts\States;
use Tochka\Promises\BaseJob;

trait DefaultPromise
{
    /** @var MayPromised[] */
    private $jobs = [];

    public function add(MayPromised $entity): void
    {
        $this->jobs[] = $entity;
    }

    public function run(): void
    {
        $basePromise = new BasePromise($this);

        if ($this instanceof DefaultTransitions) {
            $basePromise->addCondition($this->getSuccessCondition(), States::RUNNING, States::SUCCESS);
            $basePromise->addCondition($this->getFailedCondition(), States::RUNNING, States::FAILED);
            $basePromise->addCondition($this->getTimeoutCondition(), States::RUNNING, States::TIMEOUT);
        }

        $basePromise->save();

        if (empty($this->jobs)) {
            $basePromise->setState(BasePromise::SUCCESS);

            return;
        }

        $prevJob = null;

        foreach ($this->jobs as $job) {
            $queuedJob = new BaseJob($basePromise, $job);
            if ($this instanceof DefaultTransitions) {
                $queuedJob->addCondition($this->getJobRunningCondition($prevJob), States::WAITING, States::RUNNING);
            }
            $prevJob = $queuedJob;
        }

        $basePromise->dispatch();
    }
}
