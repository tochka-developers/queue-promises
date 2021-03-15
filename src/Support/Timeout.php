<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\Conditions\Timeout as TimeoutCondition;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Enums\StateEnum;

trait Timeout
{
    /** @var int|\DateInterval */
    private $trait_timeout;

    /**
     * @param int|\DateInterval $timeout Таймаут в минутах, или DateInterval
     */
    public function setTimeout($timeout): void
    {
        $this->trait_timeout = $timeout;
    }

    /**
     * @return int|\DateInterval
     */
    public function getTimeout()
    {
        return $this->timeout ?? $this->trait_timeout;
    }

    /**
     * Hook promiseConditions
     *
     * @param \Tochka\Promises\Core\BasePromise $promise
     */
    public function promiseConditionsTimeout(BasePromise $promise): void
    {
        if ($this->getTimeout() === null) {
            return;
        }

        $promise->addCondition(
            new ConditionTransition(
                new TimeoutCondition($this->getTimeout()),
                StateEnum::RUNNING(),
                StateEnum::TIMEOUT()
            )
        );
    }
}
