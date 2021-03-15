<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\Conditions\AllJobsInStates;
use Tochka\Promises\Conditions\AndConditions;
use Tochka\Promises\Conditions\OneJobInState;
use Tochka\Promises\Conditions\Positive;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Enums\StateEnum;

trait ASync
{
    /**
     * Hook promiseConditions
     *
     * @param \Tochka\Promises\Core\BasePromise $promise
     */
    public function promiseConditionsASync(BasePromise $promise): void
    {
        $promise->addCondition(
            new ConditionTransition(
                new AllJobsInStates(StateEnum::successStates()), StateEnum::RUNNING(),
                StateEnum::SUCCESS()
            )
        );

        $andCondition = new AndConditions();
        $andCondition->addCondition(new AllJobsInStates(StateEnum::finishedStates()));
        $andCondition->addCondition(new OneJobInState(StateEnum::failedStates()));

        $promise->addCondition(
            new ConditionTransition($andCondition, StateEnum::RUNNING(), StateEnum::FAILED())
        );
    }

    /**
     * Hook jobConditions
     *
     * @param \Tochka\Promises\Core\BasePromise $promise
     * @param \Tochka\Promises\Core\BaseJob     $job
     */
    public function jobConditionsASync(BasePromise $promise, BaseJob $job): void
    {
        $job->addCondition(new ConditionTransition(new Positive(), StateEnum::WAITING(), StateEnum::RUNNING()));
    }
}
