<?php

namespace Tochka\Promises\Conditions;

use Tochka\Promises\BasePromise;
use Tochka\Promises\Contracts\Condition;

class Positive implements Condition
{
    public function condition(BasePromise $basePromise): bool
    {
        return true;
    }
}
