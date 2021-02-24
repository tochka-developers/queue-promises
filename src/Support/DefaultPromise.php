<?php

namespace Tochka\Promises\Support;

use Illuminate\Support\Collection;
use Tochka\Promises\Conditions\EmptyJobs;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Facades\PromiseJobRegistry;
use Tochka\Promises\Facades\Promises;

trait DefaultPromise
{
    use BaseJobId;

    /** @var MayPromised[] */
    private $jobs = [];
    /** @var int */
    private $promise_id;

    public function add(MayPromised $entity): void
    {
        $this->jobs[] = $entity;
    }

    public function run(): void
    {
        /** @var \Tochka\Promises\Contracts\PromiseHandler $this */
        Promises::run($this, $this->jobs);
        $this->jobs = [];
    }

    public function promiseConditionsDefaultPromise(BasePromise $promise): void
    {
        $promise->addCondition(
            new ConditionTransition(new EmptyJobs(), StateEnum::RUNNING(), StateEnum::SUCCESS())
        );
    }

    public function setPromiseId(int $promise_id): void
    {
        $this->promise_id = $promise_id;
    }

    public function getPromiseId(): int
    {
        return $this->promise_id;
    }

    /**
     * @return \Illuminate\Support\Collection|BaseJob[]
     */
    public function getResults(): Collection
    {
        return PromiseJobRegistry::loadByPromiseId($this->promise_id);
    }
}
