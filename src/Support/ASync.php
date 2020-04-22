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
    public function promiseConditionsASync(BasePromise $promise): void
    {
        $promise->addCondition(
            new ConditionTransition(AllJobsInStates::success(), StateEnum::RUNNING(),
                StateEnum::SUCCESS())
        );

        $andCondition = new AndConditions();
        $andCondition->addCondition(AllJobsInStates::finished());
        $andCondition->addCondition(OneJobInState::failed());

        $promise->addCondition(
            new ConditionTransition($andCondition, StateEnum::RUNNING(), StateEnum::FAILED())
        );
    }

    public function jobConditionsASync(BasePromise $promise, BaseJob $job): void
    {
        $job->addCondition(new ConditionTransition(new Positive(), StateEnum::WAITING(), StateEnum::RUNNING()));
    }
}
