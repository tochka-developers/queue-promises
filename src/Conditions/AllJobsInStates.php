<?php

namespace Tochka\Promises\Conditions;

use Tochka\Promises\Contracts\ConditionContract;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Facades\PromiseJobRegistry;

final class AllJobsInStates implements ConditionContract
{
    /** @var StateEnum[] */
    private $states;

    public function __construct(array $states)
    {
        $this->states = $states;
    }

    public static function success(): self
    {
        return new self([StateEnum::SUCCESS()]);
    }

    public static function failed(): self
    {
        return new self([StateEnum::FAILED(), StateEnum::TIMEOUT()]);
    }

    public static function finished(): self
    {
        return new self([StateEnum::SUCCESS(), StateEnum::FAILED(), StateEnum::TIMEOUT()]);
    }

    public function condition(BasePromise $basePromise): bool
    {
        return PromiseJobRegistry::loadByPromiseIdCursor($basePromise->getPromiseId())->reduce(
            static function (bool $carry, BaseJob $job) {
                return $carry && $job->getState()->in($this->states);
            },
            true);
    }
}
