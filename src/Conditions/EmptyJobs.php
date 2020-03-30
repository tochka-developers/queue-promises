<?php

namespace Tochka\Promises\Conditions;

use Tochka\Promises\Contracts\ConditionContract;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Facades\PromiseJobRegistry;

class EmptyJobs implements ConditionContract
{
    public function condition(BasePromise $basePromise): bool
    {
        return PromiseJobRegistry::countByPromiseId($basePromise->getPromiseId()) === 0;
    }
}
