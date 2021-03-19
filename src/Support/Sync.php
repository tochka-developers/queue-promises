<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\Conditions\AllJobsInStates;
use Tochka\Promises\Conditions\JobInState;
use Tochka\Promises\Conditions\OneJobInState;
use Tochka\Promises\Conditions\Positive;
use Tochka\Promises\Conditions\PromiseInState;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Enums\StateEnum;

trait Sync
{
    private ?BaseJob $previousJob = null;

    /**
     * Hook promiseConditions
     *
     * @param \Tochka\Promises\Core\BasePromise $promise
     */
    public function promiseConditionsSync(BasePromise $promise): void
    {
        // Если все задачи перешли в состояние success - меняем состояние промиса на success
        $promise->addCondition(
            new ConditionTransition(
                new AllJobsInStates(StateEnum::successStates()),
                StateEnum::RUNNING(),
                StateEnum::SUCCESS()
            )
        );
        // Если хотя бы одна задача в состоянии failed или timout - меняем состояние промиса на failed
        $promise->addCondition(
            new ConditionTransition(
                new OneJobInState(StateEnum::failedStates()),
                StateEnum::RUNNING(),
                StateEnum::FAILED()
            )
        );
    }

    /**
     * Hook jobConditions
     *
     * @param \Tochka\Promises\Core\BasePromise $promise
     * @param \Tochka\Promises\Core\BaseJob     $job
     */
    public function jobConditionsSync(BasePromise $promise, BaseJob $job): void
    {
        if ($this->previousJob === null) {
            // первая задача стартует сразу
            $conditionTransition = new ConditionTransition(new Positive(), StateEnum::WAITING(), StateEnum::RUNNING());
        } else {
            // каждая следующая задача стартует после успешного завершения предыдущей
            $conditionTransition = new ConditionTransition(
                new JobInState($this->previousJob, StateEnum::successStates()),
                StateEnum::WAITING(),
                StateEnum::RUNNING()
            );
        }

        $job->addCondition($conditionTransition);

        // если основной промис завершился - то все ждущие задачи переходят в состояние отмененных
        $job->addCondition(
            new ConditionTransition(
                new PromiseInState(StateEnum::finishedStates()),
                StateEnum::WAITING(),
                StateEnum::CANCELED()
            )
        );
        $job->addCondition(
            new ConditionTransition(
                new PromiseInState(StateEnum::finishedStates()),
                StateEnum::RUNNING(),
                StateEnum::TIMEOUT()
            )
        );

        $this->previousJob = $job;
    }

    /**
     * Hook afterRun
     */
    public function afterRunSync(): void
    {
        $this->previousJob = null;
    }
}
