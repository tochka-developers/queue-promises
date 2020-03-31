<?php

namespace Tochka\Promises\Listeners\Support;

trait FilterTransitions
{
    /**
     * @param \Tochka\Promises\Events\StateChanged $event
     */
    public function handle($event): void
    {
        if (!isset($this->transitions)) {
            return;
        }

        foreach ($this->transitions as $method => $transition) {
            if (
                method_exists($this, $method)
                && (($transition['from'] ?? '') === '*' || $event->getFromState()->in($transition['from'] ?? []))
                && (($transition['to'] ?? '') === '*' || $event->getToState()->in($transition['to'] ?? []))
            ) {
                $this->$method($event);
            }
        }
    }
}