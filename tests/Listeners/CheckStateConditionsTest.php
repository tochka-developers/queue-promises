<?php

namespace Tochka\Promises\Tests\Listeners;

use Tochka\Promises\Contracts\ConditionContract;
use Tochka\Promises\Contracts\StateChangedContract;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\PromiseJobStateChanged;
use Tochka\Promises\Events\PromiseStateChanged;
use Tochka\Promises\Listeners\CheckStateConditions;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseJob;
use Tochka\Promises\Tests\TestCase;

/**
 * @covers \Tochka\Promises\Listeners\CheckStateConditions
 */
class CheckStateConditionsTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Listeners\CheckStateConditions::handle
     */
    public function testHandlePromiseJobStateChanged(): void
    {
        $trueMock = \Mockery::mock(ConditionContract::class);
        $trueMock->shouldReceive('condition')
            ->andReturn(true);

        /** @var Promise $promise */
        $promise = Promise::factory()->create();
        $basePromise = $promise->getBasePromise();

        $conditionTransition = new ConditionTransition($trueMock, StateEnum::WAITING(), StateEnum::RUNNING());

        /** @var PromiseJob $promiseJob */
        $promiseJob = PromiseJob::factory()->create(['promise_id' => $promise->id]);
        /** @var PromiseJob $expectedJob */
        $expectedJob = PromiseJob::factory()->create(
            [
                'promise_id' => $promise->id,
                'state'      => StateEnum::WAITING(),
            ]
        );
        $baseJob = $promiseJob->getBaseJob();
        $promiseJob->promise = $promise;
        $expectedJob->promise = $promise;

        $event = new PromiseJobStateChanged($baseJob, StateEnum::RUNNING(), StateEnum::SUCCESS());

        /** @var CheckStateConditions|\Mockery\Mock $listener */
        $listener = \Mockery::mock(CheckStateConditions::class);
        $listener->makePartial();
        $listener->shouldReceive('getConditionsForState')
            ->twice()
            ->andReturn([$conditionTransition]);

        $listener->shouldReceive('getTransitionForConditions')
            ->twice()
            ->with([$conditionTransition], $basePromise)
            ->andReturn($conditionTransition);

        $listener->handle($event);

        $expectedJob->refresh();

        self::assertEquals(StateEnum::RUNNING(), $expectedJob->state);
        self::assertEquals(StateEnum::RUNNING(), $expectedJob->getBaseJob()->getState());
    }

    /**
     * @covers \Tochka\Promises\Listeners\CheckStateConditions::handle
     */
    public function testHandlePromiseJobStateChangedEmptyPromise(): void
    {
        /** @var PromiseJob $promiseJob */
        $promiseJob = PromiseJob::factory()->create(['promise_id' => 1]);
        $baseJob = $promiseJob->getBaseJob();

        $event = new PromiseJobStateChanged($baseJob, StateEnum::RUNNING(), StateEnum::SUCCESS());

        /** @var CheckStateConditions|\Mockery\Mock $listener */
        $listener = \Mockery::mock(CheckStateConditions::class);
        $listener->makePartial();
        $listener->shouldReceive('getConditionsForState')
            ->never();

        $listener->shouldReceive('getTransitionForConditions')
            ->never();

        $listener->handle($event);
    }

    /**
     * @covers \Tochka\Promises\Listeners\CheckStateConditions::handle
     */
    public function testHandlePromiseStateChanged(): void
    {
        $trueMock = \Mockery::mock(ConditionContract::class);
        $trueMock->shouldReceive('condition')
            ->andReturn(true);

        /** @var Promise $promise */
        $promise = Promise::factory()->create();
        $basePromise = $promise->getBasePromise();

        /** @var PromiseJob $promiseJob */
        $promiseJob = PromiseJob::factory()->create(['promise_id' => $promise->id]);
        $promiseJob->promise = $promise;

        $event = new PromiseStateChanged($basePromise, StateEnum::RUNNING(), StateEnum::SUCCESS());

        $conditionTransition = new ConditionTransition($trueMock, StateEnum::RUNNING(), StateEnum::SUCCESS());

        /** @var CheckStateConditions|\Mockery\Mock $listener */
        $listener = \Mockery::mock(CheckStateConditions::class);
        $listener->makePartial();
        $listener->shouldReceive('getConditionsForState')
            ->twice()
            ->andReturn([$conditionTransition]);

        $listener->shouldReceive('getTransitionForConditions')
            ->twice()
            ->with([$conditionTransition], $basePromise)
            ->andReturn($conditionTransition);

        $listener->handle($event);

        $promiseJob->refresh();

        self::assertEquals(StateEnum::SUCCESS(), $promiseJob->state);
        self::assertEquals(StateEnum::SUCCESS(), $promiseJob->getBaseJob()->getState());
    }

    /**
     * @covers \Tochka\Promises\Listeners\CheckStateConditions::handle
     */
    public function testHandleUnknownEvent(): void
    {
        $event = \Mockery::mock(StateChangedContract::class);

        /** @var CheckStateConditions|\Mockery\Mock $listener */
        $listener = \Mockery::mock(CheckStateConditions::class);
        $listener->makePartial();
        $listener->shouldReceive('getConditionsForState')
            ->never();

        $listener->shouldReceive('getTransitionForConditions')
            ->never();

        $listener->handle($event);
    }
}
