<?php

namespace Tochka\Promises\Tests\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tochka\Promises\Models\PromiseEvent;
use Tochka\Promises\Support\WaitEvent;
use Tochka\Promises\Tests\TestCase;

/**
 * @covers \Tochka\Promises\Models\PromiseEvent
 */
class PromiseEventTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Event::fake();
    }

    /**
     * @covers \Tochka\Promises\Models\PromiseEvent::getWaitEvent
     * @covers \Tochka\Promises\Models\PromiseEvent::saveWaitEvent
     */
    public function testSaveWaitEvent(): void
    {
        $eventName = 'MyTestEventName';
        $eventId = 'unique_id';
        $baseJobId = 123;

        $waitEvent = new WaitEvent($eventName, $eventId);
        $waitEvent->setBaseJobId($baseJobId);
        PromiseEvent::saveWaitEvent($waitEvent);

        self::assertEquals(1, $waitEvent->getId());

        $event = PromiseEvent::find(1);

        self::assertEquals($baseJobId, $event->job_id);
        self::assertEquals($eventName, $event->event_name);
        self::assertEquals($eventId, $event->event_unique_id);

        $resultWaitEvent = $event->getWaitEvent();

        self::assertEquals(1, $resultWaitEvent->getId());
        self::assertEquals($event, $resultWaitEvent->getAttachedModel());
        self::assertEquals($baseJobId, $resultWaitEvent->getBaseJobId());
        self::assertEquals($eventName, $resultWaitEvent->getEventName());
        self::assertEquals($eventId, $resultWaitEvent->getEventUniqueId());
    }

    /**
     * @covers \Tochka\Promises\Models\PromiseEvent::scopeByJob
     */
    public function testByJob(): void
    {
        PromiseEvent::factory()->create(['id' => 1, 'job_id' => 121]);
        PromiseEvent::factory()->create(['id' => 2, 'job_id' => 122]);
        PromiseEvent::factory()->create(['id' => 3, 'job_id' => 123]);
        PromiseEvent::factory()->create(['id' => 4, 'job_id' => 124]);

        /** @var PromiseEvent $promiseJob */
        $promiseJob = PromiseEvent::byJob(122)->first();

        self::assertEquals(2, $promiseJob->id);
    }

    /**
     * @covers \Tochka\Promises\Models\PromiseEvent::scopeByEvent
     */
    public function testByEvent(): void
    {
        PromiseEvent::factory()->create(['id' => 1, 'event_name' => 'not']);
        PromiseEvent::factory()->create(['id' => 2, 'event_name' => 'yes', 'event_unique_id' => 1]);
        PromiseEvent::factory()->create(['id' => 3, 'event_name' => 'not']);
        PromiseEvent::factory()->create(['id' => 4, 'event_name' => 'not']);

        /** @var PromiseEvent $promiseEvent */
        $promiseEvent = PromiseEvent::byEvent('yes', 1)->first();

        self::assertEquals(2, $promiseEvent->id);
    }
}
