<?php

namespace Tochka\Promises\Support;

use Tochka\Promises\Conditions\Timeout as TimeoutCondition;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Enums\StateEnum;

trait Timeout
{
    private $trait_timeout_minutes;
    private $trait_timeout;

    /**
     * @param int|string $timeout Таймаут в минутах (int), Таймаут в любых единицаx (string в формате 10h8m12s)
     */
    public function setTimeout($timeout): void
    {
        if (\is_int($timeout)) {
            $this->trait_timeout_minutes = $timeout;
            $this->trait_timeout = $timeout . 'm';
            return;
        }

        $this->trait_timeout = $timeout;
    }

    /**
     * Возвращает таймаут в минутах (если задан)
     * @return int|null
     * @deprecated
     */
    public function getTimeout(): ?int
    {
        return $this->timeout ?? $this->trait_timeout_minutes;
    }

    /**
     * Возвращает полный таймаут
     * @return string|null
     */
    public function getFullTimeout(): ?string
    {
        if (isset($this->timeout)) {
            return $this->timeout . 'm';
        }

        return $this->trait_timeout;
    }

    public function promiseConditionsTimeout(BasePromise $promise): void
    {
        if ($this->getFullTimeout() === null) {
            return;
        }

        $promise->addCondition(new ConditionTransition(
            new TimeoutCondition($this->getFullTimeout()),
            StateEnum::RUNNING(),
            StateEnum::TIMEOUT()
        ));
    }
}