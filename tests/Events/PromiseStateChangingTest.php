<?php

namespace Tochka\Promises\Tests\Events;

use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\PromiseStateChanging;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestPromise;

/**
 * @covers \Tochka\Promises\Events\PromiseStateChanging
 */
class PromiseStateChangingTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Events\PromiseStateChanging::getToState
     * @covers \Tochka\Promises\Events\PromiseStateChanging::getFromState
     * @covers \Tochka\Promises\Events\PromiseStateChanging::getPromise
     */
    public function testEvent(): void
    {
        $basePromise = new BasePromise(new TestPromise());
        $event = new PromiseStateChanging($basePromise, StateEnum::WAITING(), StateEnum::RUNNING());

        self::assertEquals($basePromise, $event->getPromise());
        self::assertEquals(StateEnum::WAITING(), $event->getFromState());
        self::assertEquals(StateEnum::RUNNING(), $event->getToState());
    }
}
