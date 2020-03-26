<?php

namespace Tochka\Promises\Conditions;

use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Contracts\ConditionContract;

class Timeout implements ConditionContract
{
    /** @var int */
    private $timeout;

    public function __construct(int $timeout)
    {
        $this->timeout = $timeout;
    }

    public function condition(BasePromise $basePromise): bool
    {
        return false;
    }
}
