<?php

namespace Tochka\Promises;

use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Contracts\States;
use Tochka\Promises\Support\Database;

class BaseJob implements States
{
    use FSM, ConditionTransitions, Database;

    /** @var int */
    private $promise_id;
    /** @var \Tochka\Promises\Contracts\MayPromised */
    private $initial_job;
    /** @var \Tochka\Promises\Contracts\MayPromised */
    private $result_job;

    public function __construct(BasePromise $promise, MayPromised $job)
    {
        $this->promise_id = $promise->getId();
        $this->initial_job = $job;
        $this->state = self::WAITING;

        $this->save();
    }


    public function setResult(MayPromised $job): void
    {
        $this->result_job = $job;
    }

    protected function saveFields()
    {
        return [
            'conditions'  => serialize($this->conditions),
            'state'       => $this->state,
            'promise_id'  => $this->promise_id,
            'initial_job' => serialize($this->initial_job),
            'result_job'  => serialize($this->result_job),
        ];
    }

    protected function getFields(array $fields)
    {
        $this->conditions = unserialize($fields['conditions']);
        $this->state = $fields['state'] ?? null;
        $this->promise_id = $fields['promise_id'] ?? null;
        $this->initial_job = unserialize($fields['initial_job']);
        $this->result_job = unserialize($fields['result_job']);
    }
}
