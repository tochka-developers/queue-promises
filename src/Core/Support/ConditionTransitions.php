<?php

namespace Tochka\Promises\Core\Support;

trait ConditionTransitions
{
    /** @var array<ConditionTransition> */
    private array $conditions = [];

    public function addCondition(ConditionTransition $conditionTransition): void
    {
        $this->conditions[] = $conditionTransition;
    }

    /**
     * @return array<ConditionTransition>
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * @param array<ConditionTransition> $conditions
     */
    public function setConditions(array $conditions): void
    {
        $this->conditions = $conditions;
    }
}
