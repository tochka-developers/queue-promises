<?php

namespace Tochka\Promises\Contracts;

use Tochka\Promises\Core\BaseJob;

interface DefaultTransitions
{
    /**
     * @return \Tochka\Promises\Contracts\ConditionContract
     */
    public function getSuccessCondition(): ConditionContract;

    /**
     * @return \Tochka\Promises\Contracts\ConditionContract
     */
    public function getFailedCondition(): ConditionContract;

    /**
     * @return \Tochka\Promises\Contracts\ConditionContract
     */
    public function getTimeoutCondition(): ConditionContract;

    /**
     * @param \Tochka\Promises\Core\BaseJob|null $previousJob
     *
     * @return \Tochka\Promises\Contracts\ConditionContract
     */
    public function getJobRunningCondition(?BaseJob $previousJob): ConditionContract;
}
