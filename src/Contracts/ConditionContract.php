<?php

namespace Tochka\Promises\Contracts;

use Tochka\Promises\Core\BasePromise;

/**
 * @api
 */
interface ConditionContract
{
    public function condition(BasePromise $basePromise): bool;
}
