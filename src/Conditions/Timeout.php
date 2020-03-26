<?php

namespace Tochka\Promises\Conditions;

use Tochka\Promises\BasePromise;
use Tochka\Promises\Contracts\Condition;

class Timeout implements Condition
{
    /** @var int */
    private int $timeout;

    public function __construct(int $timeout)
    {
        $this->timeout = $timeout;
    }

    public function condition(BasePromise $basePromise): bool
    {
        return false;
    }
}
