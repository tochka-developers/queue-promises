<?php

namespace Tochka\Promises\Core;

trait FSM
{
    /** @var string|null */
    private $state = null;

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $old_state = $this->state;
        $this->state = $state;

        $this->onTransition($old_state, $state);
    }

    public function restoreState(string $state): void
    {
        $this->state = $state;
    }

    public function onTransition(?string $from_state, string $to_state): void
    {
        $eventMethodName = $this->getEventMethodName($from_state, $to_state);

        if (method_exists($this, $eventMethodName)) {
            $this->$eventMethodName();
        }
    }

    private function getEventMethodName(?string $from_state, string $to_state): string
    {
        if ($from_state === null) {
            return 'transitionTo' . ucfirst($to_state);
        }

        return 'transitionFrom' . ucfirst($from_state) . 'To' . ucfirst($to_state);
    }
}
