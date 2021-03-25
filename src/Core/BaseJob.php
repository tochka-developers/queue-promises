<?php

namespace Tochka\Promises\Core;

use Tochka\Promises\Contracts\ConditionTransitionsContract;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Contracts\StatesContract;
use Tochka\Promises\Core\Support\ConditionTransitions;
use Tochka\Promises\Core\Support\States;
use Tochka\Promises\Core\Support\Time;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\PromiseJob;

class BaseJob implements StatesContract, ConditionTransitionsContract
{
    use ConditionTransitions;
    use States;
    use Time;

    private ?int $id = null;
    private ?int $promise_id;
    private MayPromised $initial_job;
    private MayPromised $result_job;
    private ?\Throwable $exception = null;
    private PromiseJob $model;

    public function __construct(int $promise_id, MayPromised $initial_job, MayPromised $result_job = null)
    {
        $this->promise_id = $promise_id;
        $this->initial_job = $initial_job;
        $this->result_job = $result_job ?: $initial_job;
        $this->state = StateEnum::WAITING();
        $this->model = new PromiseJob();
    }

    public function setInitial(MayPromised $job): void
    {
        $this->initial_job = $job;
    }

    public function setResult($job): void
    {
        $this->result_job = $job;
    }

    public function setException(?\Throwable $exception): void
    {
        $this->exception = $exception;
    }

    public function getJobId(): ?int
    {
        return $this->id;
    }

    public function setJobId(int $id): void
    {
        $this->id = $id;
    }

    public function getPromiseId(): int
    {
        return $this->promise_id;
    }

    public function getInitialJob(): MayPromised
    {
        return $this->initial_job;
    }

    public function getResultJob(): MayPromised
    {
        return $this->result_job;
    }

    public function getException(): ?\Throwable
    {
        return $this->exception;
    }

    public function getAttachedModel(): PromiseJob
    {
        return $this->model;
    }

    public function setAttachedModel(PromiseJob $model): void
    {
        $this->model = $model;
    }
}
