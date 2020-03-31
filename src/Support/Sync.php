<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\Conditions\AllJobsIsSuccessState;
use Tochka\Promises\Conditions\JobIsSuccessState;
use Tochka\Promises\Conditions\OneJobIsFailedState;
use Tochka\Promises\Conditions\Positive;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Enums\StateEnum;

trait Sync
{
    private $previousJob = null;

    public function promiseConditionsSync(BasePromise $promise): void
    {
        $promise->addCondition(
            new ConditionTransition(new AllJobsIsSuccessState(), StateEnum::RUNNING(), StateEnum::SUCCESS())
        );
        $promise->addCondition(
            new ConditionTransition(new OneJobIsFailedState(), StateEnum::RUNNING(), StateEnum::FAILED())
        );
    }

    public function jobConditionsSync(BaseJob $job): void
    {
        if ($this->previousJob === null) {
            $conditionTransition = new ConditionTransition(new Positive(), StateEnum::WAITING(), StateEnum::RUNNING());
        } else {
            $conditionTransition = new ConditionTransition(
                new JobIsSuccessState($this->previousJob),
                StateEnum::WAITING(),
                StateEnum::RUNNING()
            );
        }

        $job->addCondition($conditionTransition);

        $this->previousJob = $job;
    }
}
