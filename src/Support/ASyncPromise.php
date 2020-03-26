<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Conditions\AllJobsIsFinished;
use Tochka\Promises\Conditions\AllJobsIsSuccessState;
use Tochka\Promises\Conditions\AndConditions;
use Tochka\Promises\Conditions\OneJobIsFailedState;
use Tochka\Promises\Conditions\Positive;
use Tochka\Promises\Conditions\Timeout;
use Tochka\Promises\Contracts\Condition;

trait ASyncPromise
{
    public function getSuccessCondition(): Condition
    {
        return new AllJobsIsSuccessState();
    }

    public function getFailedCondition(): Condition
    {
        $andCondition = new AndConditions();
        $andCondition->addCondition(new AllJobsIsFinished());
        $andCondition->addCondition(new OneJobIsFailedState());

        return $andCondition;
    }

    public function getTimeoutCondition(): Condition
    {
        return new Timeout(10);
    }

    public function getJobRunningCondition(?BaseJob $previousJob): Condition
    {
        return new Positive();
    }
}
