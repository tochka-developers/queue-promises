<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\Contracts\DefaultTransitions;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Enums\StateEnum;
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
                new ConditionTransition($this->getSuccessCondition(), StateEnum::RUNNING(), StateEnum::SUCCESS())
            );
            $basePromise->addCondition(
                new ConditionTransition($this->getFailedCondition(), StateEnum::RUNNING(), StateEnum::FAILED())
            );
            $basePromise->addCondition(
                new ConditionTransition($this->getTimeoutCondition(), StateEnum::RUNNING(), StateEnum::TIMEOUT())
            );
        }

        if (empty($this->jobs)) {
            $basePromise->setState(StateEnum::SUCCESS());
            PromiseRegistry::save($basePromise);

            return;
        }

        PromiseRegistry::save($basePromise);

        $prevJob = null;

        foreach ($this->jobs as $job) {
            $baseJob = new BaseJob($basePromise->getPromiseId(), $job);
            PromiseJobRegistry::save($baseJob);

            $job->setBaseJobId($baseJob->getJobId());
            $baseJob->setInitial($job);

            if ($this instanceof DefaultTransitions) {
                $baseJob->addCondition(
                    new ConditionTransition(
                        $this->getJobRunningCondition($prevJob),
                        StateEnum::WAITING(),
                        StateEnum::RUNNING()
                    )
                );
            }

            PromiseJobRegistry::save($baseJob);

            $prevJob = $baseJob;
        }

        $basePromise->dispatch();
    }
}
