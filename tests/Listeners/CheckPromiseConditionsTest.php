<?php

namespace Tochka\Promises\Tests\Listeners;

use Tochka\Promises\Contracts\ConditionContract;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\PromiseJobStateChanged;
use Tochka\Promises\Listeners\CheckPromiseConditions;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseJob;
use Tochka\Promises\Tests\TestCase;

/**
 * @covers \Tochka\Promises\Listeners\CheckPromiseConditions
 */
class CheckPromiseConditionsTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Listeners\CheckPromiseConditions::handle
     */
    public function testHandle(): void
    {
        /** @var Promise $promise */
        $promise = Promise::factory()->create();
        $basePromise = $promise->getBasePromise();

        /** @var PromiseJob $promiseJob */
        $promiseJob = PromiseJob::factory()->create(['promise_id' => $promise->id]);
        $promiseJob->promise = $promise;
        $event = new PromiseJobStateChanged($promiseJob->getBaseJob(), StateEnum::WAITING(), StateEnum::RUNNING());

        $trueMock = \Mockery::mock(ConditionContract::class);
        $trueMock->shouldReceive('condition')
            ->andReturn(true);

        $conditionTransition = new ConditionTransition($trueMock, StateEnum::RUNNING(), StateEnum::SUCCESS());

        /** @var CheckPromiseConditions|\Mockery\Mock $listener */
        $listener = \Mockery::mock(CheckPromiseConditions::class);
        $listener->makePartial();
        $listener->shouldReceive('getConditionsForState')
            ->once()
            ->with($basePromise, $basePromise)
            ->andReturn([$conditionTransition]);

        $listener->shouldReceive('getTransitionForConditions')
            ->once()
            ->with([$conditionTransition], $basePromise)
            ->andReturn($conditionTransition);

        $listener->handle($event);

        self::assertEquals(StateEnum::SUCCESS(), $basePromise->getState());
        self::assertEquals(StateEnum::SUCCESS(), $promise->state);
    }

    /**
     * @covers \Tochka\Promises\Listeners\CheckPromiseConditions::handle
     */
    public function testHandleNoPromise(): void
    {
        /** @var PromiseJob $promiseJob */
        $promiseJob = PromiseJob::factory()->create(['promise_id' => 1]);
        $event = new PromiseJobStateChanged($promiseJob->getBaseJob(), StateEnum::WAITING(), StateEnum::RUNNING());

        /** @var CheckPromiseConditions|\Mockery\Mock $listener */
        $listener = \Mockery::mock(CheckPromiseConditions::class)->makePartial();
        $listener->shouldReceive('getConditionsForState')
            ->never();

        $listener->shouldReceive('getTransitionForConditions')
            ->never();

        $listener->handle($event);
    }
}
