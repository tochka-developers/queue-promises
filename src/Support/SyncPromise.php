<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\BaseJob;
use Tochka\Promises\Conditions\AllJobsIsSuccessState;
use Tochka\Promises\Conditions\JobIsSuccessState;
use Tochka\Promises\Conditions\OneJobIsFailedState;
use Tochka\Promises\Conditions\Positive;
use Tochka\Promises\Conditions\Timeout;
use Tochka\Promises\Contracts\Condition;

trait SyncPromise
{
    public function getSuccessCondition(): Condition
    {
        return new AllJobsIsSuccessState();
    }

    public function getFailedCondition(): Condition
    {
        return new OneJobIsFailedState();
    }

    public function getTimeoutCondition(): Condition
    {
        return new Timeout(10);
    }

    public function getJobRunningCondition(?BaseJob $previousJob): Condition
    {
        if ($previousJob === null) {
            return new Positive();
        }

        return new JobIsSuccessState($previousJob);
    }
}
