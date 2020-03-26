<?php

namespace Tochka\Promises\Contracts;

use Tochka\Promises\Core\BasePromise;

interface ConditionContract
{
    public function condition(BasePromise $basePromise): bool;
}
