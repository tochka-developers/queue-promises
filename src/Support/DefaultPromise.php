<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Contracts\DefaultTransitions;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Contracts\States;
use Tochka\Promises\Facades\PromiseJobRegistry;
use Tochka\Promises\Facades\PromiseRegistry;

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
        /** @var \Tochka\Promises\Contracts\PromiseHandler $this */
        $basePromise = new BasePromise($this);

        // добавляем условия перехода между состояниями промиса
        if ($this instanceof DefaultTransitions) {
            $basePromise->addCondition(
                new ConditionTransition($this->getSuccessCondition(), States::RUNNING, States::SUCCESS)
            );
            $basePromise->addCondition(
                new ConditionTransition($this->getFailedCondition(), States::RUNNING, States::FAILED)
            );
            $basePromise->addCondition(
                new ConditionTransition($this->getTimeoutCondition(), States::RUNNING, States::TIMEOUT)
            );
        }

        PromiseRegistry::save($basePromise);

        if (empty($this->jobs)) {
            $basePromise->setState(BasePromise::SUCCESS);

            return;
        }

        $prevJob = null;

        foreach ($this->jobs as $job) {
            $queuedJob = new BaseJob($basePromise->getPromiseId(), $job);
            if ($this instanceof DefaultTransitions) {
                $queuedJob->addCondition(
                    new ConditionTransition($this->getJobRunningCondition($prevJob), States::WAITING, States::RUNNING)
                );
            }
            PromiseJobRegistry::save($queuedJob);
            $prevJob = $queuedJob;
        }

        $basePromise->dispatch();
    }
}
