<?php

namespace Tochka\Promises\Conditions;

use Tochka\Promises\BasePromise;
use Tochka\Promises\Contracts\Condition;

class AndConditions implements Condition
{
    /** @var \Tochka\Promises\Contracts\Condition[] */
    private $conditions = [];

    public function addCondition(Condition $condition): self
    {
        $this->conditions[] = $condition;

        return $this;
    }

    public function condition(BasePromise $basePromise): bool
    {
        return array_reduce($this->conditions, static function (bool $carry, Condition $item) use ($basePromise) {
            return $carry && $item->condition($basePromise);
        }, true);
    }
}
