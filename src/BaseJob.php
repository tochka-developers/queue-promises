<?php

namespace Tochka\Promises;

use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Contracts\States;

class BaseJob implements States
{
    use FSM, ConditionTransitions;

    private ?int $id = null;
    private int $promise_id;
    private MayPromised $initial_job;
    private MayPromised $result_job;

    public function __construct(int $promise_id, MayPromised $initial_job, MayPromised $result_job = null)
    {
        $this->promise_id = $promise_id;
        $this->initial_job = $initial_job;
        $this->result_job = $result_job ?: $initial_job;
        $this->state = self::WAITING;
    }

    public function setResult(MayPromised $job): void
    {
        $this->result_job = $job;
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
}
