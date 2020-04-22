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
    private $previousJob;

    public function promiseConditionsSync(BasePromise $promise): void
    {
        $promise->addCondition(
            new ConditionTransition(AllJobsInStates::success(), StateEnum::RUNNING(), StateEnum::SUCCESS())
        );
        $promise->addCondition(
            new ConditionTransition(OneJobInState::failed(), StateEnum::RUNNING(), StateEnum::FAILED())
        );
    }

    public function jobConditionsSync(BasePromise $promise, BaseJob $job): void
    {
        if ($this->previousJob === null) {
            // первая задача стартует сразу
            $conditionTransition = new ConditionTransition(new Positive(), StateEnum::WAITING(), StateEnum::RUNNING());
        } else {
            // каждая следующая задача стартует после успешного завершения предыдущей
            $conditionTransition = new ConditionTransition(
                JobInState::success($this->previousJob),
                StateEnum::WAITING(),
                StateEnum::RUNNING()
            );
        }

        $job->addCondition($conditionTransition);

        // если основной промис завершился - то все ждущие задачи переходят в состояние отмененных
        $job->addCondition(
            new ConditionTransition(PromiseInState::finished(), StateEnum::WAITING(), StateEnum::CANCELED())
        );

        $this->previousJob = $job;
    }
}
