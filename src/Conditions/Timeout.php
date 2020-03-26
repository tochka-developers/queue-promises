<?php

namespace Tochka\Promises\Conditions;

use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Contracts\Condition;

class Timeout implements Condition
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
