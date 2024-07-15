<?php

namespace Tochka\Promises\Tests\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tochka\Promises\Conditions\Positive;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\PromiseJobStateChanged;
use Tochka\Promises\Events\PromiseJobStateChanging;
use Tochka\Promises\Events\StateChanged;
use Tochka\Promises\Events\StateChanging;
use Tochka\Promises\Models\PromiseJob;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestDirtyJob;
use Tochka\Promises\Tests\TestHelpers\TestJob;

/**
 * @covers \Tochka\Promises\Models\PromiseJob
 */
class PromiseJobTest extends TestCase
{
    use RefreshDatabase;

    public function saveProvider(): array
    {
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

        return [
            'Full'  => [
                121,
                StateEnum::WAITING(),
                $conditions,
                new TestJob('initial'),
                new TestJob('result'),
                //new \RuntimeException('Test'),
                null,
            ],
            'Empty' => [
                122,
                StateEnum::SUCCESS(),
                [],
                new TestJob('initial'),
                null,
                null,
            ],
            'Dirty' => [
                122,
                StateEnum::SUCCESS(),
                [],
                new TestDirtyJob('initial'),
                null,
                null,
            ],
        ];
    }

    /**
     * @dataProvider saveProvider
     * @covers       \Tochka\Promises\Models\PromiseJob::saveBaseJob
     * @covers       \Tochka\Promises\Models\PromiseJob::getBaseJob
     */
    public function testSaveBaseJob(
        int $promiseId,
        ?StateEnum $state,
        array $conditions,
        MayPromised $initialJob,
        ?MayPromised $resultJob,
        ?\Throwable $exception,
    ): void {
        Event::fake();

        $baseJob = new BaseJob($promiseId, $initialJob, $resultJob);
        $baseJob->setState($state);
        $baseJob->setConditions($conditions);
        $baseJob->setException($exception);

        PromiseJob::saveBaseJob($baseJob);

        self::assertEquals(1, $baseJob->getJobId());

        $promiseJob = PromiseJob::find(1);

        self::assertEquals($promiseId, $promiseJob->promise_id);
        self::assertEquals($state, $promiseJob->state);
        if ($initialJob instanceof TestDirtyJob) {
            $initialJob->job = null;
        }
        self::assertEquals($initialJob, $promiseJob->initial_job);
        if ($resultJob === null) {
            self::assertEquals($initialJob, $promiseJob->result_job);
        } else {
            self::assertEquals($resultJob, $promiseJob->result_job);
        }
        self::assertEquals($exception, $promiseJob->exception);

        $resultBaseJob = $promiseJob->getBaseJob();

        self::assertEquals(1, $resultBaseJob->getJobId());
        self::assertEquals($promiseJob, $resultBaseJob->getAttachedModel());
        self::assertEquals($promiseId, $resultBaseJob->getPromiseId());
        self::assertEquals($state, $resultBaseJob->getState());
        self::assertEquals($initialJob, $resultBaseJob->getInitialJob());
        if ($resultJob === null) {
            self::assertEquals($initialJob, $resultBaseJob->getResultJob());
        } else {
            self::assertEquals($resultJob, $resultBaseJob->getResultJob());
        }
        self::assertEquals($exception, $resultBaseJob->getException());
    }

    /**
     * @covers \Tochka\Promises\Models\PromiseJob::scopeByPromise
     */
    public function testByPromise(): void
    {
        PromiseJob::factory()->create(['id' => 1, 'promise_id' => 121]);
        PromiseJob::factory()->create(['id' => 2, 'promise_id' => 122]);
        PromiseJob::factory()->create(['id' => 3, 'promise_id' => 123]);
        PromiseJob::factory()->create(['id' => 4, 'promise_id' => 124]);

        $promiseJob = PromiseJob::byPromise(122)->first();

        self::assertEquals(2, $promiseJob->id);
    }

    /**
     * @covers \Tochka\Promises\Models\PromiseJob::boot
     */
    public function testBoot(): void
    {
        Event::fake(
            [
                StateChanging::class,
                StateChanged::class,
                PromiseJobStateChanging::class,
                PromiseJobStateChanged::class,
            ],
        );

        $job = PromiseJob::factory()->create(['state' => StateEnum::WAITING()]);
        $job->setChangedState(StateEnum::WAITING());

        $job->state = StateEnum::SUCCESS();
        $job->save();

        Event::assertDispatched(
            function (StateChanging $event) use ($job) {
                self::assertEquals($job->getBaseJob(), $event->getInstance());
                self::assertEquals(StateEnum::WAITING(), $event->getFromState());
                self::assertEquals(StateEnum::SUCCESS(), $event->getToState());

                return true;
            },
        );

        Event::assertDispatched(
            function (StateChanged $event) use ($job) {
                self::assertEquals($job->getBaseJob(), $event->getInstance());
                self::assertEquals(StateEnum::WAITING(), $event->getFromState());
                self::assertEquals(StateEnum::SUCCESS(), $event->getToState());

                return true;
            },
        );

        Event::assertDispatched(
            function (PromiseJobStateChanging $event) use ($job) {
                self::assertEquals($job->getBaseJob(), $event->getPromiseJob());
                self::assertEquals(StateEnum::WAITING(), $event->getFromState());
                self::assertEquals(StateEnum::SUCCESS(), $event->getToState());

                return true;
            },
        );

        Event::assertDispatched(
            function (PromiseJobStateChanged $event) use ($job) {
                self::assertEquals($job->getBaseJob(), $event->getPromiseJob());
                self::assertEquals(StateEnum::WAITING(), $event->getFromState());
                self::assertEquals(StateEnum::SUCCESS(), $event->getToState());

                return true;
            },
        );
    }
}
