<?php

namespace Tochka\Promises\Tests\Core\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tochka\Promises\Contracts\PromisedEvent;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\Support\EventDispatcher;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\PromiseEvent;
use Tochka\Promises\Models\PromiseJob;
use Tochka\Promises\Support\WaitEvent;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestEvent;

/**
 * @covers \Tochka\Promises\Core\Support\EventDispatcher
 */
class EventDispatcherTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @covers \Tochka\Promises\Core\Support\EventDispatcher::dispatch
     */
    public function testDispatch(): void
    {
        $eventUniqueId = 'unique_test';
        $promisedEvent = new TestEvent($eventUniqueId);

        $waitEvent = new WaitEvent(get_class($promisedEvent), $eventUniqueId);

        $baseJob = new BaseJob(1, $waitEvent);
        PromiseJob::saveBaseJob($baseJob);

        $waitEvent->setBaseJobId($baseJob->getJobId());
        PromiseEvent::saveWaitEvent($waitEvent);

        $dispatcher = \Mockery::mock(EventDispatcher::class);
        $dispatcher->makePartial();

        $dispatcher->shouldReceive('updateEventState')
            ->once()
            ->with(
                $promisedEvent,
                \Mockery::on(
                    function ($actual) use ($waitEvent) {
                        return $actual instanceof WaitEvent
                            && $actual->getEventName() === $waitEvent->getEventName()
                            && $actual->getEventUniqueId() === $waitEvent->getEventUniqueId()
                            && $actual->getId() === $waitEvent->getId()
                            && $actual->getBaseJobId() === $waitEvent->getBaseJobId();
                    },
                ),
            );

        $dispatcher->dispatch($promisedEvent);
    }

    /**
     * @covers \Tochka\Promises\Core\Support\EventDispatcher::dispatch
     */
    public function testDispatchNoEvents(): void
    {
        $promisedEvent = \Mockery::mock(PromisedEvent::class);
        $promisedEvent->shouldReceive('getUniqueId')
            ->once()
            ->andReturn(1);

        $dispatcher = \Mockery::mock(EventDispatcher::class);
        $dispatcher->makePartial();

        $dispatcher->shouldReceive('updateEventState')
            ->never();

        $dispatcher->dispatch($promisedEvent);
    }

    /**
     * @covers \Tochka\Promises\Core\Support\EventDispatcher::dispatch
     */
    public function testUpdateEventState(): void
    {
        $eventUniqueId = 'unique_test';
        $promisedEvent = new TestEvent($eventUniqueId);

        $waitEvent = new WaitEvent(get_class($promisedEvent), $eventUniqueId);

        $baseJob = new BaseJob(1, $waitEvent);
        $jobModel = PromiseJob::saveBaseJob($baseJob);

        $waitEvent->setBaseJobId($baseJob->getJobId());
        PromiseEvent::saveWaitEvent($waitEvent);

        $dispatcher = new EventDispatcher();

        $dispatcher->updateEventState($promisedEvent, $waitEvent);

        $jobModel->refresh();
        $baseJob = $jobModel->getBaseJob();
        /** @var WaitEvent $actualWaitEvent */
        $actualWaitEvent = $baseJob->getResultJob();

        self::assertEquals(StateEnum::SUCCESS(), $baseJob->getState());
        self::assertEquals($waitEvent->getId(), $actualWaitEvent->getId());
        self::assertEquals($waitEvent->getEventName(), $actualWaitEvent->getEventName());
        self::assertEquals($waitEvent->getEventUniqueId(), $actualWaitEvent->getEventUniqueId());
        self::assertEquals($promisedEvent, $actualWaitEvent->getEvent());
    }
}
