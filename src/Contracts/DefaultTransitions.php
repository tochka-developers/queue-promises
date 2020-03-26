<?php

namespace Tochka\Promises\Contracts;

use Tochka\Promises\Core\BaseJob;

interface DefaultTransitions
{
    public function getSuccessCondition(): ConditionContract;

    public function getFailedCondition(): ConditionContract;

    public function getTimeoutCondition(): ConditionContract;

    public function getJobRunningCondition(?BaseJob $previousJob): ConditionContract;
}
