<?php

namespace Tochka\Promises\Conditions;

use Tochka\Promises\Contracts\ConditionContract;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Enums\StateEnum;

final class PromiseInState implements ConditionContract
{
    /** @var array<StateEnum> */
    private array $states;

    public function __construct(array $states)
    {
        $this->states = $states;
    }

    public function getStates(): array
    {
        return $this->states;
    }

    public function condition(BasePromise $basePromise): bool
    {
        return $basePromise->getState()->in($this->states);
    }
}
