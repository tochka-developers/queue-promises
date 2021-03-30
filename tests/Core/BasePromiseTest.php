<?php

namespace Tochka\Promises\Tests\Core;

use Carbon\Carbon;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestPromise;

/**
 * @covers \Tochka\Promises\Core\BasePromise
 */
class BasePromiseTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Core\BasePromise::getAttachedModel
     * @covers \Tochka\Promises\Core\BasePromise::setAttachedModel
     */
    public function testGetSetAttachedModel(): void
    {
        $expected = new Promise();

        $mock = \Mockery::mock(BasePromise::class);
        $mock->makePartial();

        $mock->setAttachedModel($expected);
        $result = $mock->getAttachedModel();

        self::assertEquals($expected, $result);
    }

    /**
     * @covers \Tochka\Promises\Core\BasePromise::setWatchAt
     * @covers \Tochka\Promises\Core\BasePromise::getWatchAt
     */
    public function testGetSetWatchAt(): void
    {
        $expected = Carbon::now();

        $mock = \Mockery::mock(BasePromise::class);
        $mock->makePartial();

        $mock->setWatchAt($expected);
        $result = $mock->getWatchAt();

        self::assertEquals($expected, $result);
    }

    /**
     * @covers \Tochka\Promises\Core\BasePromise::getPromiseHandler
     * @covers \Tochka\Promises\Core\BasePromise::setPromiseHandler
     */
    public function testGetSetPromiseHandler(): void
    {
        $expected = new TestPromise();

        $mock = \Mockery::mock(BasePromise::class);
        $mock->makePartial();

        $mock->setPromiseHandler($expected);
        $result = $mock->getPromiseHandler();

        self::assertEquals($expected, $result);
    }

    /**
     * @covers \Tochka\Promises\Core\BasePromise::getPromiseId
     * @covers \Tochka\Promises\Core\BasePromise::setPromiseId
     */
    public function testGetSetPromiseId(): void
    {
        $expected = 123;

        $mock = \Mockery::mock(BasePromise::class);
        $mock->makePartial();

        self::assertNull($mock->getPromiseId());

        $mock->setPromiseId($expected);
        $result = $mock->getPromiseId();

        self::assertEquals($expected, $result);
    }

    /**
     * @covers \Tochka\Promises\Core\BasePromise::getTimeoutAt
     * @covers \Tochka\Promises\Core\BasePromise::setTimeoutAt
     */
    public function testGetSetTimeoutAt(): void
    {
        $expected = Carbon::now();

        $mock = \Mockery::mock(BasePromise::class);
        $mock->makePartial();

        $mock->setTimeoutAt($expected);
        $result = $mock->getTimeoutAt();

        self::assertEquals($expected, $result);
    }

    /**
     * @covers \Tochka\Promises\Core\BasePromise::dispatch
     */
    public function testDispatch(): void
    {
        $basePromise = new BasePromise(new TestPromise());

        $basePromise->dispatch();

        $basePromise->getAttachedModel()->refresh();
        $actualModel = $basePromise->getAttachedModel();

        self::assertEquals(StateEnum::RUNNING(), $actualModel->state);
    }

    /**
     * @covers \Tochka\Promises\Core\BasePromise::__construct
     */
    public function testConstruct(): void
    {
        Carbon::setTestNow(Carbon::now());

        $expectedHandler = new TestPromise();
        $expectedWatchAt = Carbon::now()->addSeconds(watcher_watch_timeout());
        $expectedTimeoutAt = Carbon::now()->addSeconds(432000);

        $basePromise = new BasePromise($expectedHandler);

        self::assertEquals($expectedHandler, $basePromise->getPromiseHandler());
        self::assertEquals(StateEnum::WAITING(), $basePromise->getState());
        self::assertEquals($expectedWatchAt, $basePromise->getWatchAt());
        self::assertEquals($expectedTimeoutAt, $basePromise->getTimeoutAt());
    }
}
