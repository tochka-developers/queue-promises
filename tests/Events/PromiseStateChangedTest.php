<?php

namespace Tochka\Promises\Tests\Events;

use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\PromiseStateChanged;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestPromise;

/**
 * @covers \Tochka\Promises\Events\PromiseStateChanged
 */
class PromiseStateChangedTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Events\PromiseStateChanged::getToState
     * @covers \Tochka\Promises\Events\PromiseStateChanged::getFromState
     * @covers \Tochka\Promises\Events\PromiseStateChanged::getPromise
     */
    public function testEvent(): void
    {
        $basePromise = new BasePromise(new TestPromise());
        $event = new PromiseStateChanged($basePromise, StateEnum::WAITING(), StateEnum::RUNNING());

        self::assertEquals($basePromise, $event->getPromise());
        self::assertEquals(StateEnum::WAITING(), $event->getFromState());
        self::assertEquals(StateEnum::RUNNING(), $event->getToState());
    }
}
