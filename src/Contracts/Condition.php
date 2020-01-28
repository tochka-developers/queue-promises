<?php

namespace Tochka\Promises\Contracts;

use Tochka\Promises\BasePromise;

interface Condition
{
    public function condition(BasePromise $basePromise): bool;
}
