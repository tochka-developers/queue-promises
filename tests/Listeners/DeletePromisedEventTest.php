<?php

namespace Tochka\Promises\Tests\Listeners;

use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\PromiseJobStateChanged;
use Tochka\Promises\Listeners\DeletePromisedEvent;
use Tochka\Promises\Models\PromiseEvent;
use Tochka\Promises\Tests\TestCase;

/**
 * @covers \Tochka\Promises\Listeners\DeletePromisedEvent
 */
class DeletePromisedEventTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Listeners\DeletePromisedEvent::dispatchJob
     * @throws \Exception
     */
    public function testDispatchJobAttached(): void
    {
        /** @var PromiseEvent $promisedEvent */
        $promisedEvent = PromiseEvent::factory()->create();
        $waitEvent = $promisedEvent->getWaitEvent();

        $job = new BaseJob(1, $waitEvent);

        $event = new PromiseJobStateChanged($job, StateEnum::RUNNING(), StateEnum::SUCCESS());
        $listener = new DeletePromisedEvent();
        $listener->dispatchJob($event);

        $promisedEventDB = PromiseEvent::find($promisedEvent->id);

        self::assertNull($promisedEventDB);
    }

    /**
     * @covers \Tochka\Promises\Listeners\DeletePromisedEvent::dispatchJob
     * @throws \Exception
     */
    public function testDispatchJobModel(): void
    {
        /** @var PromiseEvent $promisedEvent */
        $promisedEvent = PromiseEvent::factory()->create();
        $waitEvent = $promisedEvent->getWaitEvent();
        $waitEvent->setAttachedModel(null);

        $job = new BaseJob(1, $waitEvent);

        $event = new PromiseJobStateChanged($job, StateEnum::RUNNING(), StateEnum::SUCCESS());
        $listener = new DeletePromisedEvent();
        $listener->dispatchJob($event);

        $promisedEventDB = PromiseEvent::find($promisedEvent->id);

        self::assertNull($promisedEventDB);
    }
}
