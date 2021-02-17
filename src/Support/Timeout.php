<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\Conditions\Timeout as TimeoutCondition;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Enums\StateEnum;

trait Timeout
{
    private $trait_timeout_minutes;
    private $trait_timeout_seconds;

    /**
     * @param int $timeoutMinutes Таймаут в минутах
     * @param int $timeoutSeconds Таймаут в секундах (необязательный)
     */
    public function setTimeout(int $timeoutMinutes, int $timeoutSeconds = 0): void
    {
        $this->trait_timeout_minutes = $timeoutMinutes;
        $this->trait_timeout_seconds = $timeoutSeconds;
    }

    public function getTimeout(): ?int
    {
        return $this->timeout ?? $this->trait_timeout_minutes;
    }

    public function getFullTimeout(): array
    {
        return [$this->getTimeout(), $this->trait_timeout_seconds];
    }

    public function promiseConditionsTimeout(BasePromise $promise): void
    {
        if ($this->getTimeout() === null) {
            return;
        }

        $promise->addCondition(new ConditionTransition(
            new TimeoutCondition(...$this->getFullTimeout()),
            StateEnum::RUNNING(),
            StateEnum::TIMEOUT()
        ));
    }
}