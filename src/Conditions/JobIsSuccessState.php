<?php

namespace Tochka\Promises\Conditions;

use Tochka\Promises\Contracts\ConditionContract;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Facades\PromiseJobRegistry;

class JobIsSuccessState implements ConditionContract
{
    /** @var int */
    private $job_id;

    public function __construct(BaseJob $job)
    {
        $this->job_id = $job->getJobId();
    }

    public function condition(BasePromise $basePromise): bool
    {
        $job = PromiseJobRegistry::load($this->job_id);

        if (!$job) {
            return true;
        }

        return $job->getState()->is(StateEnum::SUCCESS());
    }
}
