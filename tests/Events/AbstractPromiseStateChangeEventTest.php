<?php

namespace Tochka\Promises\Tests\Events;

use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\AbstractPromiseStateChangeEvent;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestPromise;

/**
 * @covers \Tochka\Promises\Events\AbstractPromiseStateChangeEvent
 */
class AbstractPromiseStateChangeEventTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Events\AbstractPromiseStateChangeEvent::getToState
     * @covers \Tochka\Promises\Events\AbstractPromiseStateChangeEvent::getFromState
     * @covers \Tochka\Promises\Events\AbstractPromiseStateChangeEvent::getPromise
     */
    public function testEvent(): void
    {
        $basePromise = new BasePromise(new TestPromise());
        $mock = \Mockery::mock(
            AbstractPromiseStateChangeEvent::class,
            [$basePromise, StateEnum::WAITING(), StateEnum::RUNNING()]
        );
        $mock->makePartial();

        self::assertEquals($basePromise, $mock->getPromise());
        self::assertEquals(StateEnum::WAITING(), $mock->getFromState());
        self::assertEquals(StateEnum::RUNNING(), $mock->getToState());
    }
}
