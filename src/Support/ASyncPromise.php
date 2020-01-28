<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\BaseJob;
use Tochka\Promises\Conditions\AllJobsIsFinished;
use Tochka\Promises\Conditions\AllJobsIsSuccessState;
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
        return new AllJobsIsFinished();
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
