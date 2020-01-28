<?php

namespace Tochka\Promises\Conditions;

use Tochka\Promises\BaseJob;
use Tochka\Promises\BasePromise;
use Tochka\Promises\Contracts\Condition;
use Tochka\Promises\Contracts\States;

class JobIsSuccessState implements Condition
{
    /** @var int */
    private $job_id;

    public function __construct(BaseJob $job)
    {
        $this->job_id = $job->getId();
    }

    public function condition(BasePromise $basePromise): bool
    {
        $job = $basePromise->getJob($this->job_id);

        if (!$job) {
            return true;
        }

        return $job->getState() === States::SUCCESS;
    }
}
