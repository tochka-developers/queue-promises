<?php

namespace Tochka\Promises\Conditions;

use Tochka\Promises\Contracts\ConditionContract;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Models\PromiseJob;

final class EmptyJobs implements ConditionContract
{
    public function condition(BasePromise $basePromise): bool
    {
        return PromiseJob::byPromise($basePromise->getPromiseId())->count() === 0;
    }
}
