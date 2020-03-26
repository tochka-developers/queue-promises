<?php

namespace Tochka\Promises\Core\Support;

trait ConditionTransitions
{
    /** @var \Tochka\Promises\Core\Support\ConditionTransition[] */
    private $conditions = [];

    public function addCondition(ConditionTransition $conditionTransition): void
    {
        $this->conditions[] = $conditionTransition;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function setConditions(array $conditions): void
    {
        $this->conditions = $conditions;
    }
}
