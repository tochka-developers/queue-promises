<?php

namespace Tochka\Promises\Conditions;

use Tochka\Promises\Contracts\ConditionContract;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\PromiseJob;

final class JobInState implements ConditionContract
{
    /** @var int */
    private $job_id;
    /** @var StateEnum[] */
    private $states;

    public function __construct(BaseJob $job, array $states)
    {
        $this->job_id = $job->getJobId();
        $this->states = $states;
    }

    public static function success(BaseJob $job): self
    {
        return new self($job, [StateEnum::SUCCESS()]);
    }

    public static function failed(BaseJob $job): self
    {
        return new self($job, [StateEnum::FAILED(), StateEnum::TIMEOUT()]);
    }

    public static function finished(BaseJob $job): self
    {
        return new self($job, [StateEnum::SUCCESS(), StateEnum::FAILED(), StateEnum::TIMEOUT()]);
    }

    public function condition(BasePromise $basePromise): bool
    {
        $jobModel = PromiseJob::find($this->job_id);
        if ($jobModel === null) {
            return true;
        }

        $job = $jobModel->getBaseJob();

        if (!$job) {
            return true;
        }

        return $job->getState()->in($this->states);
    }
}
