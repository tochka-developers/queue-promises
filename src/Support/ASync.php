<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\Conditions\AllJobsIsFinished;
use Tochka\Promises\Conditions\AllJobsIsSuccessState;
use Tochka\Promises\Conditions\AndConditions;
use Tochka\Promises\Conditions\OneJobIsFailedState;
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
            new ConditionTransition(new AllJobsIsSuccessState(), StateEnum::RUNNING(), StateEnum::SUCCESS())
        );

        $andCondition = new AndConditions();
        $andCondition->addCondition(new AllJobsIsFinished());
        $andCondition->addCondition(new OneJobIsFailedState());

        $promise->addCondition(
            new ConditionTransition($andCondition, StateEnum::RUNNING(), StateEnum::FAILED())
        );
    }

    public function jobConditionsASync(BaseJob $job): void
    {
        $job->addCondition(new ConditionTransition(new Positive(), StateEnum::WAITING(), StateEnum::RUNNING()));
    }
}
