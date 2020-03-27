<?php

namespace Tochka\Promises\Conditions;

use Tochka\Promises\Contracts\ConditionContract;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Facades\PromiseJobRegistry;

class AllJobsIsFinished implements ConditionContract
{
    public function condition(BasePromise $basePromise): bool
    {
        return PromiseJobRegistry::loadByPromiseIdCursor($basePromise->getPromiseId())->reduce(
            static function (bool $carry, BaseJob $job) {
                return $carry && $job->getState()->in([StateEnum::SUCCESS, StateEnum::FAILED, StateEnum::TIMEOUT]);
            },
            true);
    }
}
