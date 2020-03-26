<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Conditions\AllJobsIsFinished;
use Tochka\Promises\Conditions\AllJobsIsSuccessState;
use Tochka\Promises\Conditions\AndConditions;
use Tochka\Promises\Conditions\OneJobIsFailedState;
use Tochka\Promises\Conditions\Positive;
use Tochka\Promises\Conditions\Timeout;
use Tochka\Promises\Contracts\ConditionContract;

trait ASyncPromise
{
    public function getSuccessCondition(): ConditionContract
    {
        return new AllJobsIsSuccessState();
    }

    public function getFailedCondition(): ConditionContract
    {
        $andCondition = new AndConditions();
        $andCondition->addCondition(new AllJobsIsFinished());
        $andCondition->addCondition(new OneJobIsFailedState());

        return $andCondition;
    }

    public function getTimeoutCondition(): ConditionContract
    {
        return new Timeout(10);
    }

    public function getJobRunningCondition(?BaseJob $previousJob): ConditionContract
    {
        return new Positive();
    }
}
