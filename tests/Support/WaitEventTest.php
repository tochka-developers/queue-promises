<?php

namespace Tochka\Promises\Tests\Support;

use Tochka\Promises\Models\PromiseEvent;
use Tochka\Promises\Support\WaitEvent;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestEvent;

/**
 * @covers \Tochka\Promises\Support\WaitEvent
 */
class WaitEventTest extends TestCase
{

    /**
     * @covers \Tochka\Promises\Support\WaitEvent::setAttachedModel
     * @covers \Tochka\Promises\Support\WaitEvent::getAttachedModel
     */
    public function testSetGetAttachedModel(): void
    {
        $expected = new PromiseEvent();

        $mock = \Mockery::mock(WaitEvent::class);
        $mock->makePartial();

        self::assertNull($mock->getAttachedModel());

        $mock->setAttachedModel($expected);
        $result = $mock->getAttachedModel();

        self::assertEquals($expected, $result);
    }

    /**
     * @covers \Tochka\Promises\Support\WaitEvent::setEvent
     * @covers \Tochka\Promises\Support\WaitEvent::getEvent
     */
    public function testSetGetEvent(): void
    {
        $expected = new TestEvent(1);

        $mock = \Mockery::mock(WaitEvent::class);
        $mock->makePartial();

        self::assertNull($mock->getEvent());

        $mock->setEvent($expected);
        $result = $mock->getEvent();

        self::assertEquals($expected, $result);
    }

    /**
     * @covers \Tochka\Promises\Support\WaitEvent::__construct
     * @covers \Tochka\Promises\Support\WaitEvent::getEventName
     * @covers \Tochka\Promises\Support\WaitEvent::getEventUniqueId
     */
    public function testGetEventNameAndId(): void
    {
        $expectedEventName = 'testEvent';
        $expectedEventId = 'testEventId';

        $waitEvent = new WaitEvent($expectedEventName, $expectedEventId);

        self::assertEquals($expectedEventName, $waitEvent->getEventName());
        self::assertEquals($expectedEventId, $waitEvent->getEventUniqueId());
    }

    /**
     * @covers \Tochka\Promises\Support\WaitEvent::setId
     * @covers \Tochka\Promises\Support\WaitEvent::getId
     */
    public function testSetGetId(): void
    {
        $expected = 1;

        $mock = \Mockery::mock(WaitEvent::class);
        $mock->makePartial();

        self::assertNull($mock->getId());

        $mock->setId($expected);
        $result = $mock->getId();

        self::assertEquals($expected, $result);
    }
}
