<?php

namespace Tochka\Promises\Tests\Models;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tochka\Promises\Conditions\Positive;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\PromiseStateChanged;
use Tochka\Promises\Events\PromiseStateChanging;
use Tochka\Promises\Events\StateChanged;
use Tochka\Promises\Events\StateChanging;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestPromise;

/**
 * @covers \Tochka\Promises\Models\Promise
 */
class PromiseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @covers \Tochka\Promises\Models\Promise::saveBasePromise
     * @covers \Tochka\Promises\Models\Promise::getBasePromise
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testBasePromise(): void
    {
        Event::fake();

        $conditions = [
            new ConditionTransition(
                new Positive(),
                StateEnum::RUNNING(),
                StateEnum::SUCCESS(),
            ),
            new ConditionTransition(
                new Positive(),
                StateEnum::RUNNING(),
                StateEnum::FAILED(),
            ),
        ];

        $promiseHandler = new TestPromise();

        $basePromise = new BasePromise($promiseHandler);
        $basePromise->setState(StateEnum::RUNNING());
        foreach ($conditions as $condition) {
            $basePromise->addCondition($condition);
        }

        Promise::saveBasePromise($basePromise);

        self::assertEquals(1, $basePromise->getPromiseId());

        $promise = Promise::find(1);

        self::assertEquals($promiseHandler, $promise->promise_handler);
        self::assertEquals(StateEnum::RUNNING(), $promise->state);
        self::assertEquals($conditions, $promise->conditions);

        $resultBasePromise = $promise->getBasePromise();

        self::assertEquals(1, $resultBasePromise->getPromiseId());
        self::assertEquals($promiseHandler, $resultBasePromise->getPromiseHandler());
        self::assertEquals(StateEnum::RUNNING(), $resultBasePromise->getState());
        self::assertEquals($conditions, $resultBasePromise->getConditions());
        self::assertEquals($promise, $resultBasePromise->getAttachedModel());
    }

    /**
     * @covers \Tochka\Promises\Models\Promise::scopeInStates
     */
    public function testInStates(): void
    {
        Promise::factory()->create(['id' => 1, 'state' => StateEnum::WAITING()]);
        Promise::factory()->create(['id' => 2, 'state' => StateEnum::RUNNING()]);
        Promise::factory()->create(['id' => 3, 'state' => StateEnum::RUNNING()]);
        Promise::factory()->create(['id' => 4, 'state' => StateEnum::SUCCESS()]);

        $promises = Promise::inStates([StateEnum::WAITING(), StateEnum::SUCCESS()])->get();

        self::assertEquals(1, $promises->where('state', StateEnum::WAITING())->first()->id);
        self::assertEquals(4, $promises->where('state', StateEnum::SUCCESS())->first()->id);
    }

    /**
     * @covers \Tochka\Promises\Models\Promise::scopeInStates
     */
    public function testForWatch(): void
    {
        Carbon::setTestNow(Carbon::now());

        Promise::factory()->create(['id' => 1, 'watch_at' => Carbon::now()->addMinute(), 'timeout_at' => Carbon::now()->addMinute()]);
        Promise::factory()->create(['id' => 2, 'watch_at' => Carbon::now()->subMinute(), 'timeout_at' => Carbon::now()->addMinute()]);
        Promise::factory()->create(['id' => 3, 'watch_at' => Carbon::now()->addMinute(), 'timeout_at' => Carbon::now()->subMinute()]);
        Promise::factory()->create(['id' => 4, 'watch_at' => Carbon::now()->subMinute(), 'timeout_at' => Carbon::now()->subMinute()]);

        $promises = Promise::forWatch()->get();

        self::assertNotNull($promises->where('id', 2)->first());
        self::assertNotNull($promises->where('id', 3)->first());
        self::assertNotNull($promises->where('id', 4)->first());
        self::assertNull($promises->where('id', 1)->first());
    }

    /**
     * @covers \Tochka\Promises\Models\PromiseJob::boot
     */
    public function testBoot(): void
    {
        /** @var Promise $promise */
        $promise = Promise::factory()->create(['state' => StateEnum::WAITING()]);
        $promise->state = StateEnum::SUCCESS();

        Event::fake(
            [
                StateChanging::class,
                StateChanged::class,
                PromiseStateChanging::class,
                PromiseStateChanged::class,
            ],
        );

        $promise->save();

        Event::assertDispatched(
            function (StateChanging $event) use ($promise) {
                self::assertEquals($promise->getBasePromise(), $event->getInstance());
                self::assertEquals(StateEnum::WAITING(), $event->getFromState());
                self::assertEquals(StateEnum::SUCCESS(), $event->getToState());

                return true;
            },
        );

        Event::assertDispatched(
            function (StateChanged $event) use ($promise) {
                self::assertEquals($promise->getBasePromise(), $event->getInstance());
                self::assertEquals(StateEnum::WAITING(), $event->getFromState());
                self::assertEquals(StateEnum::SUCCESS(), $event->getToState());

                return true;
            },
        );

        Event::assertDispatched(
            function (PromiseStateChanging $event) use ($promise) {
                self::assertEquals($promise->getBasePromise(), $event->getPromise());
                self::assertEquals(StateEnum::WAITING(), $event->getFromState());
                self::assertEquals(StateEnum::SUCCESS(), $event->getToState());

                return true;
            },
        );

        Event::assertDispatched(
            function (PromiseStateChanged $event) use ($promise) {
                self::assertEquals($promise->getBasePromise(), $event->getPromise());
                self::assertEquals(StateEnum::WAITING(), $event->getFromState());
                self::assertEquals(StateEnum::SUCCESS(), $event->getToState());

                return true;
            },
        );
    }
}
