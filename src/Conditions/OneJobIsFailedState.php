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
        foreach (PromiseJobRegistry::loadByPromiseIdCursor($basePromise->getPromiseId()) as $job) {
            if ($job->getState()->in([StateEnum::FAILED(), StateEnum::TIMEOUT()])) {
                return true;
            }
        }

        return false;
    }
}
