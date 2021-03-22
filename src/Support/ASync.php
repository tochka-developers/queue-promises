<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\Conditions\AllJobsInStates;
use Tochka\Promises\Conditions\AndConditions;
use Tochka\Promises\Conditions\OneJobInState;
use Tochka\Promises\Conditions\Positive;
use Tochka\Promises\Conditions\PromiseInState;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Enums\StateEnum;

trait ASync
{
    /**
     * Hook promiseConditions
     *
     * @param \Tochka\Promises\Core\BasePromise $promise
     */
    public function promiseConditionsASync(BasePromise $promise): void
    {
        // если все задачи перешли в состояние success - то меняем состояние промиса на success
        $promise->addCondition(
            new ConditionTransition(
                new AllJobsInStates(StateEnum::successStates()),
                StateEnum::RUNNING(),
                StateEnum::SUCCESS()
            )
        );

        $andCondition = new AndConditions();
        $andCondition->addCondition(new AllJobsInStates(StateEnum::finishedStates()));
        $andCondition->addCondition(new OneJobInState(StateEnum::failedStates()));

        // Если все задачи завершены, и хотя бы одна в состоянии failed или timeout - то меняем состояние промиса на failed
        $promise->addCondition(
            new ConditionTransition($andCondition, StateEnum::RUNNING(), StateEnum::FAILED())
        );
    }

    /**
     * Hook jobConditions
     *
     * @param \Tochka\Promises\Core\BasePromise $promise
     * @param \Tochka\Promises\Core\BaseJob     $job
     */
    public function jobConditionsASync(BasePromise $promise, BaseJob $job): void
    {
        // Сразу при старте запускаем все доступные задачи в промисе
        $job->addCondition(new ConditionTransition(new Positive(), StateEnum::WAITING(), StateEnum::RUNNING()));
    }
}
