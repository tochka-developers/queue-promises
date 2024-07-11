<?php

namespace Tochka\Promises\Conditions;

use Tochka\Promises\Contracts\ConditionContract;
use Tochka\Promises\Core\BasePromise;

/**
 * @api
 */
final class OrConditions implements ConditionContract
{
    /** @var array<ConditionContract> */
    private array $conditions = [];

    public function addCondition(ConditionContract $condition): self
    {
        $this->conditions[] = $condition;

        return $this;
    }

    public function condition(BasePromise $basePromise): bool
    {
        return array_reduce(
            $this->conditions,
            static function (bool $carry, ConditionContract $item) use ($basePromise) {
                return $carry || $item->condition($basePromise);
            },
            false,
        );
    }
}
