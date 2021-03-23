<?php

namespace Tochka\Promises\Tests\Events;

use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\PromiseJobStateChanged;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestJob;

/**
 * @covers \Tochka\Promises\Events\PromiseJobStateChanged
 */
class PromiseJobStateChangedTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Events\PromiseJobStateChanged::getToState
     * @covers \Tochka\Promises\Events\PromiseJobStateChanged::getFromState
     * @covers \Tochka\Promises\Events\PromiseJobStateChanged::getPromiseJob
     */
    public function testEvent(): void
    {
        $baseJob = new BaseJob(1, new TestJob('test'));
        $event = new PromiseJobStateChanged($baseJob, StateEnum::WAITING(), StateEnum::RUNNING());

        self::assertEquals($baseJob, $event->getPromiseJob());
        self::assertEquals(StateEnum::WAITING(), $event->getFromState());
        self::assertEquals(StateEnum::RUNNING(), $event->getToState());
    }
}
