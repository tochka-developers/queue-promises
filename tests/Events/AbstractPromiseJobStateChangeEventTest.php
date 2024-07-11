<?php

namespace Tochka\Promises\Tests\Events;

use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\AbstractPromiseJobStateChangeEvent;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestJob;

/**
 * @covers \Tochka\Promises\Events\AbstractPromiseJobStateChangeEvent
 */
class AbstractPromiseJobStateChangeEventTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Events\AbstractPromiseJobStateChangeEvent::getToState
     * @covers \Tochka\Promises\Events\AbstractPromiseJobStateChangeEvent::getFromState
     * @covers \Tochka\Promises\Events\AbstractPromiseJobStateChangeEvent::getPromiseJob
     */
    public function testEvent(): void
    {
        $baseJob = new BaseJob(1, new TestJob('test'));
        $mock = \Mockery::mock(
            AbstractPromiseJobStateChangeEvent::class,
            [$baseJob, StateEnum::WAITING(), StateEnum::RUNNING()],
        );
        $mock->makePartial();

        self::assertEquals($baseJob, $mock->getPromiseJob());
        self::assertEquals(StateEnum::WAITING(), $mock->getFromState());
        self::assertEquals(StateEnum::RUNNING(), $mock->getToState());
    }
}
