<?php

namespace Tochka\Promises\Conditions;

use Tochka\Promises\Contracts\ConditionContract;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Facades\PromiseJobRegistry;

class AllJobsIsSuccessState implements ConditionContract
{
    public function condition(BasePromise $basePromise): bool
    {
        $jobs = PromiseJobRegistry::loadByPromiseId($basePromise->getPromiseId());

        return array_reduce($jobs, static function (bool $carry, BaseJob $job) {
            $state = $job->getState();

            return $carry && $state && $state->is(StateEnum::SUCCESS);
        }, true);
    }
}
