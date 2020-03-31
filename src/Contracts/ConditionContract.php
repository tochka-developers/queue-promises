<?php

namespace Tochka\Promises\Contracts;

use Tochka\Promises\Core\BasePromise;

interface ConditionContract
{
    /**
     * @param \Tochka\Promises\Core\BasePromise $basePromise
     *
     * @return bool
     */
    public function condition(BasePromise $basePromise): bool;
}
