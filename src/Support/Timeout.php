<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\Conditions\Timeout as TimeoutCondition;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Enums\StateEnum;

trait Timeout
{
    private $trait_timeout;

    /**
     * @param int $timeout Таймаут в минутах
     */
    public function setTimeout(int $timeout): void
    {
        $this->trait_timeout = $timeout;
    }

    public function getTimeout(): ?int
    {
        return $this->timeout ?? $this->trait_timeout;
    }

    public function promiseConditionsTimeout(BasePromise $promise): void
    {
        if ($this->getTimeout() === null) {
            return;
        }

        $promise->addCondition(new ConditionTransition(
            new TimeoutCondition($this->getTimeout()),
            StateEnum::RUNNING(),
            StateEnum::TIMEOUT()
        ));
    }
}