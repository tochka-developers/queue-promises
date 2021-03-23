<?php

namespace Tochka\Promises\Tests\Core\Dispatchers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\Dispatchers\WaitEventDispatcher;
use Tochka\Promises\Models\PromiseEvent;
use Tochka\Promises\Models\PromiseJob;
use Tochka\Promises\Support\WaitEvent;
use Tochka\Promises\Tests\TestCase;

/**
 * @covers \Tochka\Promises\Core\Dispatchers\WaitEventDispatcher
 */
class WaitEventDispatcherTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @covers \Tochka\Promises\Core\Dispatchers\WaitEventDispatcher::dispatch
     */
    public function testDispatch(): void
    {
        $waitEvent = new WaitEvent('TestEvent', '123');
        $baseJob = new BaseJob(1, $waitEvent);
        PromiseJob::saveBaseJob($baseJob);
        $waitEvent->setBaseJobId($baseJob->getJobId());

        $dispatcher = new WaitEventDispatcher();
        $dispatcher->dispatch($waitEvent);

        $baseJobModel = $baseJob->getAttachedModel();
        $baseJobModel->refresh();
        $initial = $baseJobModel->getBaseJob()->getInitialJob();
        self::assertInstanceOf(WaitEvent::class, $initial);

        /** @var PromiseEvent $event */
        $event = PromiseEvent::byJob($initial->getBaseJobId())->first();
        self::assertEquals($waitEvent->getEventName(), $event->event_name);
        self::assertEquals($waitEvent->getEventUniqueId(), $event->event_unique_id);
    }

    /**
     * @covers \Tochka\Promises\Core\Dispatchers\WaitEventDispatcher::mayDispatch
     */
    public function testMayDispatch(): void
    {
        $dispatcher = new WaitEventDispatcher();

        $mockQueue = \Mockery::mock(WaitEvent::class);
        $result = $dispatcher->mayDispatch($mockQueue);
        self::assertTrue($result);

        $mockNotQueue = \Mockery::mock(MayPromised::class);
        $result = $dispatcher->mayDispatch($mockNotQueue);
        self::assertFalse($result);
    }
}
