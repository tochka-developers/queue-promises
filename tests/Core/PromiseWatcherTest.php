<?php

namespace Tochka\Promises\Tests\Core;

use Carbon\Carbon;
use Hamcrest\Core\IsInstanceOf;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tochka\Promises\Contracts\ConditionTransitionsContract;
use Tochka\Promises\Contracts\StatesContract;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\PromiseWatcher;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Facades\ConditionTransitionHandler;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseJob;
use Tochka\Promises\Tests\TestCase;

/**
 * @covers \Tochka\Promises\Core\PromiseWatcher
 */
class PromiseWatcherTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @covers \Tochka\Promises\Core\PromiseWatcher::startTime
     * @covers \Tochka\Promises\Core\PromiseWatcher::calcDiffAndSleep
     */
    public function testSleep(): void
    {
        $expectedTime = 2000000;

        $mock = \Mockery::mock(PromiseWatcher::class);
        $mock->makePartial()->shouldAllowMockingProtectedMethods();

        $mock->shouldReceive('sleep')
            ->once()
            ->with($expectedTime);

        $expectedNow = Carbon::now();
        Carbon::setTestNow($expectedNow);
        $mock->startTime();
        Carbon::setTestNow($expectedNow->addSeconds(2));
        $mock->calcDiffAndSleep();
    }

    /**
     * @covers \Tochka\Promises\Core\PromiseWatcher::startTime
     * @covers \Tochka\Promises\Core\PromiseWatcher::calcDiffAndSleep
     */
    public function testSleepSmall(): void
    {
        $expectedTime = 100000;

        $mock = \Mockery::mock(PromiseWatcher::class);
        $mock->makePartial()->shouldAllowMockingProtectedMethods();

        $mock->shouldReceive('sleep')
            ->once()
            ->with($expectedTime);

        $expectedNow = Carbon::now();
        Carbon::setTestNow($expectedNow);
        $mock->startTime();
        Carbon::setTestNow($expectedNow->addMicroseconds(200));
        $mock->calcDiffAndSleep();
    }

    /**
     * @covers \Tochka\Promises\Core\PromiseWatcher::watchIteration
     */
    public function testWatchIteration(): void
    {
        Promise::factory()->count(2)->create();

        $mock = \Mockery::mock(PromiseWatcher::class);
        $mock->makePartial();

        $mock->shouldReceive('checkPromiseConditions')
            ->twice()
            ->with(IsInstanceOf::anInstanceOf(Promise::class));

        $mock->watchIteration();
    }

    /**
     * @covers \Tochka\Promises\Core\PromiseWatcher::watchIteration
     */
    public function testWatchIterationException(): void
    {
        Promise::factory()->count(2)->create();
        $expectException = new \RuntimeException('test');

        $mock = \Mockery::mock(PromiseWatcher::class);
        $mock->makePartial();

        $mock->shouldReceive('checkPromiseConditions')
            ->twice()
            ->with(IsInstanceOf::anInstanceOf(Promise::class))
            ->andThrow($expectException);

        app(ExceptionHandler::class)->shouldReport($expectException);

        $mock->watchIteration();
    }

    /**
     * @covers \Tochka\Promises\Core\PromiseWatcher::checkPromiseConditions
     */
    public function testCheckPromiseConditions(): void
    {
        $expectedWatchDiff = 10;
        $expectedNow = Carbon::now()->roundSecond();
        Carbon::setTestNow($expectedNow);

        /** @var Promise $promise */
        $promise = Promise::factory()->create();
        $basePromise = $promise->getBasePromise();

        PromiseJob::factory()->count(2)->create(['promise_id' => $promise->id]);

        $this->instance('watcher_watch_timeout', $expectedWatchDiff);

        $mock = \Mockery::mock(PromiseWatcher::class);
        $mock->makePartial();

        $mock->shouldReceive('checkJobConditions')
            ->twice()
            ->with(IsInstanceOf::anInstanceOf(PromiseJob::class), $basePromise);

        ConditionTransitionHandler::shouldReceive('checkConditionAndApplyTransition')
            ->once()
            ->with($basePromise, $basePromise, $basePromise)
            ->andReturnUsing(
                function (
                    StatesContract $statesInstance,
                    ConditionTransitionsContract $conditionTransitionsInstance,
                    BasePromise $basePromise
                ) {
                    $statesInstance->setState(StateEnum::SUCCESS());

                    return true;
                }
            );

        $mock->checkPromiseConditions($promise);

        $promise->refresh();

        self::assertEquals(StateEnum::SUCCESS(), $promise->state);
        self::assertEquals(StateEnum::SUCCESS(), $promise->getBasePromise()->getState());
        self::assertEquals($expectedNow->addSeconds($expectedWatchDiff), $promise->getBasePromise()->getWatchAt());
    }

    /**
     * @covers \Tochka\Promises\Core\PromiseWatcher::checkPromiseConditions
     */
    public function testCheckPromiseConditionsTimeout(): void
    {
        /** @var Promise $promise */
        $promise = Promise::factory()->create(['timeout_at' => Carbon::now()->subMinute()]);

        $mock = \Mockery::mock(PromiseWatcher::class);
        $mock->makePartial();

        $mock->shouldReceive('checkJobConditions')
            ->never();

        ConditionTransitionHandler::shouldReceive('checkConditionAndApplyTransition')
            ->never();

        $mock->checkPromiseConditions($promise);

        $promise->refresh();

        self::assertEquals(StateEnum::TIMEOUT(), $promise->state);
        self::assertEquals(StateEnum::TIMEOUT(), $promise->getBasePromise()->getState());
    }

    /**
     * @covers \Tochka\Promises\Core\PromiseWatcher::checkJobConditions
     */
    public function testCheckJobConditions(): void
    {
        /** @var Promise $promise */
        $promise = Promise::factory()->create();
        $basePromise = $promise->getBasePromise();

        /** @var PromiseJob $promiseJob */
        $promiseJob = PromiseJob::factory()->create(['promise_id' => $promise->id]);
        $baseJob = $promiseJob->getBaseJob();

        ConditionTransitionHandler::shouldReceive('checkConditionAndApplyTransition')
            ->once()
            ->with($baseJob, $baseJob, $basePromise)
            ->andReturnUsing(
                function (
                    StatesContract $statesInstance,
                    ConditionTransitionsContract $conditionTransitionsInstance,
                    BasePromise $basePromise
                ) {
                    $statesInstance->setState(StateEnum::SUCCESS());

                    return true;
                }
            );

        $watcher = new PromiseWatcher();
        $watcher->checkJobConditions($promiseJob, $basePromise);

        $promiseJob->refresh();

        self::assertEquals(StateEnum::SUCCESS(), $promiseJob->state);
        self::assertEquals(StateEnum::SUCCESS(), $promiseJob->getBaseJob()->getState());
    }
}
