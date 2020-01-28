<?php

namespace Tochka\Promises\Conditions;

use Tochka\Promises\BasePromise;
use Tochka\Promises\Contracts\Condition;
use Tochka\Promises\Contracts\States;

class OneJobIsFailedState implements Condition
{
    public function condition(BasePromise $basePromise): bool
    {
        $jobs = $basePromise->getJobs();

        foreach ($jobs as $job) {
            if ($job->getState() === States::FAILED) {
                return true;
            }
        }

        return false;
    }
}
