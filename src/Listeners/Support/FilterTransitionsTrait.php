<?php

namespace Tochka\Promises\Listeners\Support;

use Tochka\Promises\Contracts\StateChangedContract;

trait FilterTransitionsTrait
{
    public function handle(StateChangedContract $event): void
    {
        $transitions = $this->getTransitions();

        foreach ($transitions as $method => $transition) {
            if (
                method_exists($this, $method)
                && (($transition['from'] ?? '') === '*' || $event->getFromState()->in($transition['from'] ?? []))
                && (($transition['to'] ?? '') === '*' || $event->getToState()->in($transition['to'] ?? []))
            ) {
                $this->$method($event);
            }
        }
    }

    public function getTransitions(): array
    {
        if (!isset($this->transitions) || !is_array($this->transitions)) {
            return [];
        }

        return $this->transitions;
    }
}
