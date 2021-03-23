<?php

namespace Tochka\Promises\Tests\Events;

use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\PromiseJobStateChanging;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestJob;

/**
 * @covers \Tochka\Promises\Events\PromiseJobStateChanging
 */
class PromiseJobStateChangingTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Events\PromiseJobStateChanging::getToState
     * @covers \Tochka\Promises\Events\PromiseJobStateChanging::getFromState
     * @covers \Tochka\Promises\Events\PromiseJobStateChanging::getPromiseJob
     */
    public function testEvent(): void
    {
        $baseJob = new BaseJob(1, new TestJob('test'));
        $event = new PromiseJobStateChanging($baseJob, StateEnum::WAITING(), StateEnum::RUNNING());

        self::assertEquals($baseJob, $event->getPromiseJob());
        self::assertEquals(StateEnum::WAITING(), $event->getFromState());
        self::assertEquals(StateEnum::RUNNING(), $event->getToState());
    }
}
