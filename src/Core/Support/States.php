<?php

namespace Tochka\Promises\Core\Support;

use Tochka\Promises\Enums\StateEnum;

trait States
{
    /** @var StateEnum */
    private $state;

    public function getState(): StateEnum
    {
        return $this->state;
    }

    public function setState(StateEnum $state): void
    {
        $old_state = $this->state;
        $this->state = $state;

        $this->onTransition($old_state, $state);
    }

    public function restoreState(StateEnum $state): void
    {
        $this->state = $state;
    }

    public function onTransition(?StateEnum $from_state, StateEnum $to_state): void
    {
        $eventMethodName = $this->getEventMethodName($from_state, $to_state);

        if (method_exists($this, $eventMethodName)) {
            $this->$eventMethodName();
        }

        $anyTransitionMethod = $this->getAnyEventMethodName($to_state);

        if (method_exists($this, $anyTransitionMethod)) {
            $this->$anyTransitionMethod();
        }

        if (method_exists($this, 'anyTransition')) {
            $this->anyTransition($from_state, $to_state);
        }
    }

    private function getEventMethodName(?StateEnum $from_state, StateEnum $to_state): string
    {
        if ($from_state === null) {
            return 'transitionTo' . ucfirst($to_state->value);
        }

        return 'transitionFrom' . ucfirst($from_state->value) . 'To' . ucfirst($to_state->value);
    }

    private function getAnyEventMethodName(StateEnum $to_state): string
    {
        return 'transitionAnyTo' . ucfirst($to_state->value);
    }
}
