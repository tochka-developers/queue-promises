<?php

namespace Tochka\Promises\Contracts;

use Tochka\Promises\Core\BasePromise;

interface Condition
{
    public function condition(BasePromise $basePromise): bool;
}
