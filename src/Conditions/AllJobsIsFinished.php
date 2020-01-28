<?php

namespace Tochka\Promises\Conditions;

use Tochka\Promises\BaseJob;
use Tochka\Promises\BasePromise;
use Tochka\Promises\Contracts\Condition;
use Tochka\Promises\Contracts\States;

class AllJobsIsFinished implements Condition
{
    public function condition(BasePromise $basePromise): bool
    {
        $jobs = $basePromise->getJobs();

        return array_reduce($jobs, static function (bool $carry, BaseJob $item) {
            return $carry && (
                    $item->getState() === States::SUCCESS
                    || $item->getState() === States::FAILED
                    || $item->getState() === States::TIMEOUT
                );
        }, true);
    }
}
