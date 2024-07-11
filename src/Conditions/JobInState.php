<?php

namespace Tochka\Promises\Conditions;

use Tochka\Promises\Contracts\ConditionContract;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Enums\StateEnum;

/**
 * @api
 */
final class JobInState implements ConditionContract
{
    private ?int $job_id;
    /** @var array<StateEnum> */
    private array $states;

    public function __construct(BaseJob $job, array $states)
    {
        $this->job_id = $job->getJobId();
        $this->states = $states;
    }

    public function getStates(): array
    {
        return $this->states;
    }

    public function condition(BasePromise $basePromise): bool
    {
        $jobModel = $basePromise->getAttachedModel()->jobs->where('id', $this->job_id)->first();
        if ($jobModel === null) {
            return true;
        }

        $job = $jobModel->getBaseJob();

        return $job->getState()->in($this->states);
    }
}
