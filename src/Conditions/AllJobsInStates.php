<?php

namespace Tochka\Promises\Conditions;

use Tochka\Promises\Contracts\ConditionContract;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\PromiseJob;

final class AllJobsInStates implements ConditionContract
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
        return $basePromise->getAttachedModel()->jobs->reduce(
            function (bool $carry, PromiseJob $job) {
                return $carry && $job->getBaseJob()->getState()->in($this->states);
            },
            true
        );
    }
}
