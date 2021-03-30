<?php

namespace Tochka\Promises\Tests\Listeners;

use Hamcrest\Core\IsInstanceOf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tochka\Promises\Contracts\ConditionTransitionsContract;
use Tochka\Promises\Contracts\StateChangedContract;
use Tochka\Promises\Contracts\StatesContract;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\PromiseJobStateChanged;
use Tochka\Promises\Events\PromiseStateChanged;
use Tochka\Promises\Facades\ConditionTransitionHandler;
use Tochka\Promises\Listeners\CheckStateConditions;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseJob;
use Tochka\Promises\Tests\TestCase;

/**
 * @covers \Tochka\Promises\Listeners\CheckStateConditions
 */
class CheckStateConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @covers \Tochka\Promises\Listeners\CheckStateConditions::handle
     */
    public function testHandlePromiseJobStateChanged(): void
    {
        /** @var Promise $promise */
        $promise = Promise::factory()->create();
        $basePromise = $promise->getBasePromise();

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

        $listener = new CheckStateConditions();

        ConditionTransitionHandler::shouldReceive('checkConditionAndApplyTransition')
            ->twice()
            ->with(
                IsInstanceOf::anInstanceOf(StatesContract::class),
                IsInstanceOf::anInstanceOf(ConditionTransitionsContract::class),
                IsInstanceOf::anInstanceOf(BasePromise::class),
            )
            ->andReturnUsing(
                function (
                    StatesContract $statesInstance,
                    ConditionTransitionsContract $conditionTransitionsInstance,
                    BasePromise $basePromise
                ) {
                    $statesInstance->setState(StateEnum::RUNNING());

                    return true;
                }
            );

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

        ConditionTransitionHandler::shouldReceive('checkConditionAndApplyTransition')
            ->never();

        $listener = new CheckStateConditions();
        $listener->handle($event);
    }

    /**
     * @covers \Tochka\Promises\Listeners\CheckStateConditions::handle
     */
    public function testHandlePromiseStateChanged(): void
    {
        /** @var Promise $promise */
        $promise = Promise::factory()->create();
        $basePromise = $promise->getBasePromise();

        /** @var PromiseJob $promiseJob */
        $promiseJob = PromiseJob::factory()->create(['promise_id' => $promise->id]);
        $promiseJob->promise = $promise;

        $event = new PromiseStateChanged($basePromise, StateEnum::RUNNING(), StateEnum::SUCCESS());

        $listener = new CheckStateConditions();

        ConditionTransitionHandler::shouldReceive('checkConditionAndApplyTransition')
            ->twice()
            ->with(
                IsInstanceOf::anInstanceOf(StatesContract::class),
                IsInstanceOf::anInstanceOf(ConditionTransitionsContract::class),
                IsInstanceOf::anInstanceOf(BasePromise::class),
            )
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

        ConditionTransitionHandler::shouldReceive('checkConditionAndApplyTransition')
            ->never();

        $listener = new CheckStateConditions();
        $listener->handle($event);
    }
}
