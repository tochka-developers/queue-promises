<?php

namespace Tochka\Promises\Tests\Events;

use Tochka\Promises\Contracts\StatesContract;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\AbstractStateChangeEvent;
use Tochka\Promises\Tests\TestCase;

/**
 * @covers \Tochka\Promises\Events\AbstractStateChangeEvent
 */
class AbstractStateChangeEventTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Events\AbstractStateChangeEvent::getToState
     * @covers \Tochka\Promises\Events\AbstractStateChangeEvent::getFromState
     * @covers \Tochka\Promises\Events\AbstractStateChangeEvent::getInstance
     */
    public function testEvent(): void
    {
        $baseInstance = \Mockery::mock(StatesContract::class);
        $mock = \Mockery::mock(
            AbstractStateChangeEvent::class,
            [$baseInstance, StateEnum::WAITING(), StateEnum::RUNNING()],
        );
        $mock->makePartial();

        self::assertEquals($baseInstance, $mock->getInstance());
        self::assertEquals(StateEnum::WAITING(), $mock->getFromState());
        self::assertEquals(StateEnum::RUNNING(), $mock->getToState());
    }
}
