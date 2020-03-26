<?php

namespace Tochka\Promises;

trait ConditionTransitions
{
    /** @var \Tochka\Promises\ConditionTransition[] */
    private array $conditions = [];

    public function addCondition(ConditionTransition $conditionTransition): self
    {
        $this->conditions[] = $conditionTransition;

        return $this;
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
