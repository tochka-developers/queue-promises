<?php

namespace Tochka\Promises\Conditions;

use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Contracts\Condition;
use Tochka\Promises\Contracts\States;
use Tochka\Promises\Facades\PromiseJobRegistry;

class JobIsFinished implements Condition
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

        return $job->getState() === States::SUCCESS || $job->getState() === States::FAILED || $job->getState() === States::TIMEOUT;
    }
}
