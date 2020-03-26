<?php

namespace Tochka\Promises\Conditions;

use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Contracts\Condition;
use Tochka\Promises\Contracts\States;
use Tochka\Promises\Facades\PromiseJobRegistry;

class AllJobsIsFinished implements Condition
{
    public function condition(BasePromise $basePromise): bool
    {
        $jobs = PromiseJobRegistry::loadByPromiseId($basePromise->getPromiseId());

        return array_reduce($jobs, static function (bool $carry, BaseJob $item) {
            return $carry && (
                    $item->getState() === States::SUCCESS
                    || $item->getState() === States::FAILED
                    || $item->getState() === States::TIMEOUT
                );
        }, true);
    }
}
