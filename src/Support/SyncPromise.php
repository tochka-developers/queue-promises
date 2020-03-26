<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Conditions\AllJobsIsSuccessState;
use Tochka\Promises\Conditions\JobIsSuccessState;
use Tochka\Promises\Conditions\OneJobIsFailedState;
use Tochka\Promises\Conditions\Positive;
use Tochka\Promises\Conditions\Timeout;
use Tochka\Promises\Contracts\ConditionContract;

trait SyncPromise
{
    public function getSuccessCondition(): ConditionContract
    {
        return new AllJobsIsSuccessState();
    }

    public function getFailedCondition(): ConditionContract
    {
        return new OneJobIsFailedState();
    }

    public function getTimeoutCondition(): ConditionContract
    {
        return new Timeout(10);
    }

    public function getJobRunningCondition(?BaseJob $previousJob): ConditionContract
    {
        if ($previousJob === null) {
            return new Positive();
        }

        return new JobIsSuccessState($previousJob);
    }
}
