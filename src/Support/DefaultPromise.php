<?php

namespace Tochka\Promises\Support;

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

        PromiseRegistry::save($basePromise);

        foreach ($this->jobs as $job) {
            $baseJob = new BaseJob($basePromise->getPromiseId(), $job);
            PromiseJobRegistry::save($baseJob);

            $job->setBaseJobId($baseJob->getJobId());
            $baseJob->setInitial($job);

            foreach ($traits as $trait) {
                if (method_exists($this, $method = 'jobConditions' . class_basename($trait))) {
                    $this->$method($baseJob);
                }
            }

            PromiseJobRegistry::save($baseJob);
        }

        $basePromise->dispatch();
    }

    public function promiseConditionsDefaultPromise(BasePromise $promise): void
    {
        $promise->addCondition(
            new ConditionTransition(new EmptyJobs(), StateEnum::RUNNING(), StateEnum::SUCCESS())
        );
    }
}
