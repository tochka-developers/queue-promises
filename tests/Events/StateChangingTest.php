<?php

namespace Tochka\Promises\Tests\Events;

use Tochka\Promises\Contracts\StatesContract;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\StateChanging;
use Tochka\Promises\Tests\TestCase;

/**
 * @covers \Tochka\Promises\Events\StateChanging
 */
class StateChangingTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Events\StateChanging::getToState
     * @covers \Tochka\Promises\Events\StateChanging::getFromState
     * @covers \Tochka\Promises\Events\StateChanging::getInstance
     */
    public function testEvent(): void
    {
        $baseInstance = \Mockery::mock(StatesContract::class);

        $event = new StateChanging($baseInstance, StateEnum::WAITING(), StateEnum::RUNNING());

        self::assertEquals($baseInstance, $event->getInstance());
        self::assertEquals(StateEnum::WAITING(), $event->getFromState());
        self::assertEquals(StateEnum::RUNNING(), $event->getToState());
    }
}
