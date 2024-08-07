<?php

namespace Tochka\Promises\Support;

use Illuminate\Support\Collection;
use Tochka\Promises\Conditions\EmptyJobs;
use Tochka\Promises\Conditions\PromiseInState;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Contracts\PromiseHandler;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Facades\Promises;
use Tochka\Promises\Models\PromiseJob;

/**
 * @api
 */
trait DefaultPromise
{
    use PromisedJob;

    /** @var array<MayPromised> */
    private array $jobs = [];
    private ?int $promise_id = null;

    public function add(MayPromised $entity): void
    {
        $this->jobs[] = $entity;
    }

    public function run(): void
    {
        $jobs = $this->jobs;
        $this->jobs = [];

        /** @var PromiseHandler|self $this */
        Promises::run($this, $jobs);
    }

    public function promiseConditionsDefaultPromise(BasePromise $promise): void
    {
        $promise->addCondition(
            new ConditionTransition(new EmptyJobs(), StateEnum::RUNNING(), StateEnum::SUCCESS()),
        );
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function jobConditionsDefaultPromise(BasePromise $promise, BaseJob $job): void
    {
        // Если промис завершился - все "ждущие" по какой-либо причине задачи переводим в отмененные
        $job->addCondition(
            new ConditionTransition(
                new PromiseInState(StateEnum::finishedStates()),
                StateEnum::WAITING(),
                StateEnum::CANCELED(),
            ),
        );
        $job->addCondition(
            new ConditionTransition(
                new PromiseInState(StateEnum::finishedStates()),
                StateEnum::RUNNING(),
                StateEnum::TIMEOUT(),
            ),
        );
    }

    public function setPromiseId(int $promise_id): void
    {
        $this->promise_id = $promise_id;
    }

    public function getPromiseId(): ?int
    {
        return $this->promise_id;
    }

    /**
     * @return Collection<int, BaseJob>
     */
    public function getResults(): Collection
    {
        return PromiseJob::byPromise($this->getPromiseId())
            ->get()
            ->map(fn(PromiseJob $job) => $job->getBaseJob());
    }
}
