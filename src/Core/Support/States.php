<?php

namespace Tochka\Promises\Core\Support;

use Illuminate\Support\Facades\Event;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\StateChanged;

/**
 * Trait States
 *
 * @package Tochka\Promises\Core\Support
 */
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

    public function onTransition(StateEnum $from_state, StateEnum $to_state): void
    {
        /** @var \Tochka\Promises\Contracts\StatesContract $this */
        Event::dispatch(new StateChanged($this, $from_state, $to_state));
    }
}
