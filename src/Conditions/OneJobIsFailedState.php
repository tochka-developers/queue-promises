<?php

namespace Tochka\Promises\Conditions;

use Tochka\Promises\Contracts\ConditionContract;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Facades\PromiseJobRegistry;

class OneJobIsFailedState implements ConditionContract
{
    public function condition(BasePromise $basePromise): bool
    {
        $jobs = PromiseJobRegistry::loadByPromiseId($basePromise->getPromiseId());

        foreach ($jobs as $job) {
            $state = $job->getState();
            if ($state && $state->in([StateEnum::FAILED, StateEnum::TIMEOUT])) {
                return true;
            }
        }

        return false;
    }
}
