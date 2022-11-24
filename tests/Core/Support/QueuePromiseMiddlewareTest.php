<?php

namespace Tochka\Promises\Tests\Core\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tochka\Promises\Contracts\JobFacadeContract;
use Tochka\Promises\Contracts\JobStateContract;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Core\Support\QueuePromiseMiddleware;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\PromiseJob;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestJob;

/**
 * @covers \Tochka\Promises\Core\Support\QueuePromiseMiddleware
 */
class QueuePromiseMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @covers \Tochka\Promises\Core\Support\QueuePromiseMiddleware::handle
     * @throws \Exception
     */
    public function testHandleNotMayPromise(): void
    {
        $expected = 'assert';
        $testJob = \Mockery::mock();

        $middleware = \Mockery::mock(QueuePromiseMiddleware::class);
        $middleware->makePartial();
        $middleware->shouldReceive('setJobStateAndResult')
            ->never();

        $result = $middleware->handle($testJob, fn () => $expected);

        self::assertEquals($expected, $result);
    }

    /**
     * @covers \Tochka\Promises\Core\Support\QueuePromiseMiddleware::handle
     * @throws \Exception
     */
    public function testHandleEmptyBaseJobId(): void
    {
        $expected = 'assert';
        $testJob = \Mockery::mock(MayPromised::class);
        $testJob->shouldReceive('getBaseJobId')
            ->once()
            ->andReturn(null);

        $middleware = \Mockery::mock(QueuePromiseMiddleware::class);
        $middleware->makePartial();
        $middleware->shouldReceive('setJobStateAndResult')
            ->never();

        $result = $middleware->handle($testJob, fn () => $expected);

        self::assertEquals($expected, $result);
    }

    /**
     * @covers \Tochka\Promises\Core\Support\QueuePromiseMiddleware::handle
     * @throws \Exception
     */
    public function testHandle(): void
    {
        /** @var PromiseJob $promiseJob */
        $promiseJob = PromiseJob::factory()->create();

        $expected = 'assert';
        $testJob = \Mockery::mock(MayPromised::class);
        $testJob->shouldReceive('getBaseJobId')
            ->atLeast()
            ->once()
            ->andReturn($promiseJob->id);

        $middleware = \Mockery::mock(QueuePromiseMiddleware::class);
        $middleware->makePartial();
        $middleware->shouldReceive('setJobStateAndResult')
            ->once();

        $result = $middleware->handle($testJob, fn () => $expected);

        self::assertEquals($expected, $result);
    }

    /**
     * @covers \Tochka\Promises\Core\Support\QueuePromiseMiddleware::handle
     * @throws \Exception
     */
    public function testHandleException(): void
    {
        /** @var PromiseJob $promiseJob */
        $promiseJob = PromiseJob::factory()->create();

        $expected = 'assert';
        $testJob = \Mockery::mock(MayPromised::class);
        $testJob->shouldReceive('getBaseJobId')
            ->atLeast()
            ->once()
            ->andReturn($promiseJob->id);

        $middleware = \Mockery::mock(QueuePromiseMiddleware::class);
        $middleware->makePartial();
        $middleware->shouldReceive('setJobStateAndResult')
            ->once()
            ->andThrow(new \RuntimeException());

        $this->expectException(\RuntimeException::class);

        $result = $middleware->handle($testJob, fn () => $expected);

        self::assertEquals($expected, $result);
    }

    /**
     * @covers \Tochka\Promises\Core\Support\QueuePromiseMiddleware::setJobStateAndResult
     */
    public function testSetJobStateAndResultJobStateContract(): void
    {
        /** @var PromiseJob $promiseJob */
        $promiseJob = PromiseJob::factory()->create(['state' => StateEnum::WAITING()]);
        $expectedResult = new TestJob('test');

        $testJob = \Mockery::mock(MayPromised::class, JobStateContract::class, JobFacadeContract::class);
        $testJob->shouldReceive('getState')
            ->atLeast()
            ->once()
            ->andReturn(StateEnum::SUCCESS());
        $testJob->shouldReceive('getJobHandler')
            ->atLeast()
            ->once()
            ->andReturn($expectedResult);

        $middleware = new QueuePromiseMiddleware();
        $middleware->setJobStateAndResult($testJob, $promiseJob->getBaseJob());

        $promiseJob->refresh();
        $baseJob = $promiseJob->getBaseJob();

        self::assertEquals(StateEnum::SUCCESS(), $baseJob->getState());
        self::assertEquals($expectedResult, $baseJob->getResultJob());
    }

    /**
     * @covers \Tochka\Promises\Core\Support\QueuePromiseMiddleware::setJobStateAndResult
     */
    public function testSetJobStateAndResultJobFacadeContract(): void
    {
        /** @var PromiseJob $promiseJob */
        $promiseJob = PromiseJob::factory()->create(['state' => StateEnum::WAITING()]);
        $expectedResult = new TestJob('test');

        $testJob = \Mockery::mock(MayPromised::class, JobFacadeContract::class);
        $testJob->shouldReceive('getJobHandler')
            ->atLeast()
            ->once()
            ->andReturn($expectedResult);

        $middleware = new QueuePromiseMiddleware();
        $middleware->setJobStateAndResult($testJob, $promiseJob->getBaseJob());

        $promiseJob->refresh();
        $baseJob = $promiseJob->getBaseJob();

        self::assertEquals(StateEnum::WAITING(), $baseJob->getState());
        self::assertEquals($expectedResult, $baseJob->getResultJob());
    }

    /**
     * @covers \Tochka\Promises\Core\Support\QueuePromiseMiddleware::setJobStateAndResult
     */
    public function testSetJobStateAndResultSimpleJob(): void
    {
        /** @var PromiseJob $promiseJob */
        $promiseJob = PromiseJob::factory()->create(['state' => StateEnum::WAITING()]);
        $expectedJob = new TestJob('test');

        $middleware = new QueuePromiseMiddleware();
        $middleware->setJobStateAndResult($expectedJob, $promiseJob->getBaseJob());

        $promiseJob->refresh();
        $baseJob = $promiseJob->getBaseJob();

        self::assertEquals(StateEnum::WAITING(), $baseJob->getState());
        self::assertEquals($expectedJob, $baseJob->getResultJob());
    }
}
