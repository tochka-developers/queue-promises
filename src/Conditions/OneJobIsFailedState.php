<?php

namespace Tochka\Promises\Conditions;

use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Contracts\Condition;
use Tochka\Promises\Contracts\States;
use Tochka\Promises\Facades\PromiseJobRegistry;

class OneJobIsFailedState implements Condition
{
    public function condition(BasePromise $basePromise): bool
    {
        $jobs = PromiseJobRegistry::loadByPromiseId($basePromise->getPromiseId());

        foreach ($jobs as $job) {
            if ($job->getState() === States::FAILED) {
                return true;
            }
        }

        return false;
    }
}
