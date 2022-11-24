<?php

namespace Tochka\Promises\Tests\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tochka\Promises\Contracts\PromiseHandler;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\PromiseRunner;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseJob;
use Tochka\Promises\Support\BaseJobId;
use Tochka\Promises\Support\DefaultPromise;
use Tochka\Promises\Support\PromisedJob;
use Tochka\Promises\Support\Sync;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestConditionTrait;
use Tochka\Promises\Tests\TestHelpers\TestJob;
use Tochka\Promises\Tests\TestHelpers\TestPromise;

/**
 * @covers \Tochka\Promises\Core\PromiseRunner
 */
class PromiseRunnerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @covers \Tochka\Promises\Core\PromiseRunner::run
     */
    public function testRun(): void
    {
        $testHandler = new TestPromise();

        $jobs = [
            new TestJob('test1'),
            new TestJob('test2'),
        ];

        $runner = \Mockery::mock(PromiseRunner::class);
        $runner->makePartial();

        $runner->shouldReceive('hookTraitsMethod')
            ->once()
            ->with($testHandler, 'promiseConditions', \Mockery::any());

        $runner->shouldReceive('hookTraitsMethod')
            ->twice()
            ->with($testHandler, 'jobConditions', \Mockery::any(), \Mockery::any());

        $runner->shouldReceive('hookTraitsMethod')
            ->once()
            ->with($testHandler, 'afterRun');

        $runner->run($testHandler, $jobs);

        self::assertNotNull($testHandler->getPromiseId());

        $promiseModel = Promise::find($testHandler->getPromiseId());
        $basePromise = $promiseModel->getBasePromise();

        self::assertEquals(StateEnum::RUNNING(), $basePromise->getState());

        /** @var array<PromiseJob> $promiseJobs */
        $promiseJobs = $promiseModel->jobs->all();
        self::assertCount(2, $promiseJobs);

        foreach ($promiseJobs as $job) {
            $baseJob = $job->getBaseJob();

            self::assertEquals($baseJob->getJobId(), $baseJob->getInitialJob()->getBaseJobId());
        }
    }

    /**
     * @covers \Tochka\Promises\Core\PromiseRunner::hookTraitsMethod
     */
    public function testHookTraitsMethod(): void
    {
        $testHandler = new class () implements PromiseHandler {
            use DefaultPromise;
            use TestConditionTrait;
        };

        $mock = \Mockery::mock($testHandler);
        $basePromise = new BasePromise($mock);

        $mock->shouldReceive('promiseConditionsTestConditionTrait')
            ->once()
            ->with($basePromise);

        $runner = new PromiseRunner();
        $runner->hookTraitsMethod($mock, 'promiseConditions', $basePromise);
    }

    /**
     * @covers \Tochka\Promises\Core\PromiseRunner::getHandlerTraits
     */
    public function testGetHandlerTraits(): void
    {
        $expected = [
            BaseJobId::class => BaseJobId::class,
            DefaultPromise::class => DefaultPromise::class,
            Sync::class => Sync::class,
            PromisedJob::class => PromisedJob::class,
        ];

        $testHandler = new class () implements PromiseHandler {
            use DefaultPromise;
            use Sync;
        };

        $runner = new PromiseRunner();
        $result = $runner->getHandlerTraits($testHandler);

        self::assertEquals($expected, $result);
    }
}
