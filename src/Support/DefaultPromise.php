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
use Tochka\Promises\Facades\PromiseRegistry;

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
        $basePromise = new BasePromise($this);

        $traits = class_uses_recursive($this);

        foreach ($traits as $trait) {
            if (method_exists($this, $method = 'promiseConditions' . class_basename($trait))) {
                $this->$method($basePromise);
            }
        }

        // чтобы в PromiseHandler не сохранялись сериализованные задачи
        $jobs = $this->jobs;
        $this->jobs = [];

        PromiseRegistry::save($basePromise);

        foreach ($jobs as $job) {
            $baseJob = new BaseJob($basePromise->getPromiseId(), $job);
            PromiseJobRegistry::save($baseJob);

            $job->setBaseJobId($baseJob->getJobId());
            $baseJob->setInitial($job);

            foreach ($traits as $trait) {
                if (method_exists($this, $method = 'jobConditions' . class_basename($trait))) {
                    $this->$method($basePromise, $baseJob);
                }
            }

            PromiseJobRegistry::save($baseJob);
        }

        foreach ($traits as $trait) {
            if (method_exists($this, $method = 'afterRun' . class_basename($trait))) {
                $this->$method();
            }
        }

        $basePromise->dispatch();
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
