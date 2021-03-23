<?php

namespace Tochka\Promises\Tests\Events;

use Tochka\Promises\Contracts\StatesContract;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\StateChanged;
use Tochka\Promises\Tests\TestCase;

/**
 * @covers \Tochka\Promises\Events\StateChanged
 */
class StateChangedTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Events\StateChanged::getToState
     * @covers \Tochka\Promises\Events\StateChanged::getFromState
     * @covers \Tochka\Promises\Events\StateChanged::getInstance
     */
    public function testEvent(): void
    {
        $baseInstance = \Mockery::mock(StatesContract::class);

        $event = new StateChanged($baseInstance, StateEnum::WAITING(), StateEnum::RUNNING());

        self::assertEquals($baseInstance, $event->getInstance());
        self::assertEquals(StateEnum::WAITING(), $event->getFromState());
        self::assertEquals(StateEnum::RUNNING(), $event->getToState());
    }
}
