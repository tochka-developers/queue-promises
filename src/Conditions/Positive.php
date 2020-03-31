<?php

namespace Tochka\Promises\Conditions;

use Tochka\Promises\Contracts\ConditionContract;
use Tochka\Promises\Core\BasePromise;

class Positive implements ConditionContract
{
    public function condition(BasePromise $basePromise): bool
    {
        return true;
    }
}
