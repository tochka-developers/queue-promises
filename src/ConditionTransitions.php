<?php

namespace Tochka\Promises;

use Tochka\Promises\Contracts\Condition;

trait ConditionTransitions
{
    /** @var array */
    private $conditions = [];

    public function addCondition(Condition $condition, string $from_state, string $to_state): self
    {
        $this->conditions[] = [
            'condition'  => $condition,
            'from_state' => $from_state,
            'to_state'   => $to_state,
        ];

        return $this;
    }
}
