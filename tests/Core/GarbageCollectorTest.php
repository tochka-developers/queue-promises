<?php

namespace Tochka\Promises\Tests\Core;

use Faker\Provider\Base;
use Hamcrest\Core\IsInstanceOf;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\GarbageCollector;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseEvent;
use Tochka\Promises\Models\PromiseJob;
use Tochka\Promises\Support\WaitEvent;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestPromise;

/**
 * @covers \Tochka\Promises\Core\GarbageCollector
 */
class GarbageCollectorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @covers \Tochka\Promises\Core\GarbageCollector::iteration
     */
    public function testIteration(): void
    {
        Promise::factory()->count(2)->create(['state' => StateEnum::SUCCESS()]);

        $mock = \Mockery::mock(GarbageCollector::class, [0, 0, StateEnum::finishedStates()]);
        $mock->makePartial();

        $mock->shouldReceive('checkPromiseToDelete')
            ->twice()
            ->with(IsInstanceOf::anInstanceOf(BasePromise::class));

        $mock->iteration();
    }

    /**
     * @covers \Tochka\Promises\Core\GarbageCollector::iteration
     */
    public function testIterationException(): void
    {
        $expectException = new \RuntimeException('test');
        Promise::factory()->count(2)->create(['state' => StateEnum::SUCCESS()]);

        $mock = \Mockery::mock(GarbageCollector::class, [0, 0, StateEnum::finishedStates()]);
        $mock->makePartial();

        $mock->shouldReceive('checkPromiseToDelete')
            ->twice()
            ->with(IsInstanceOf::anInstanceOf(BasePromise::class))
            ->andThrow($expectException);

        app(ExceptionHandler::class)->shouldReport($expectException);

        $mock->iteration();
    }

    /**
     * @covers \Tochka\Promises\Core\GarbageCollector::checkPromiseToDelete
     * @throws \Exception
     */
    public function testCheckPromiseToDelete(): void
    {
        $basePromise = new BasePromise(new TestPromise());
        Promise::saveBasePromise($basePromise);

        PromiseJob::factory()->count(2)->create(['promise_id' => $basePromise->getPromiseId()]);

        $mock = \Mockery::mock(GarbageCollector::class, [0, 0, StateEnum::finishedStates()]);
        $mock->makePartial();

        $mock->shouldReceive('checkHasParentPromise')
            ->once()
            ->with($basePromise)
            ->andReturn(false);

        $mock->shouldReceive('checkJobsToDelete')
            ->twice();

        $mock->checkPromiseToDelete($basePromise);

        $actualPromise = Promise::find($basePromise->getPromiseId());

        self::assertNull($actualPromise);
    }

    /**
     * @covers \Tochka\Promises\Core\GarbageCollector::checkPromiseToDelete
     * @throws \Exception
     */
    public function testCheckPromiseToDeleteHasParent(): void
    {
        $basePromise = new BasePromise(new TestPromise());
        Promise::saveBasePromise($basePromise);

        PromiseJob::factory()->count(2)->create(['promise_id' => $basePromise->getPromiseId()]);

        $mock = \Mockery::mock(GarbageCollector::class, [0, 0, StateEnum::finishedStates()]);
        $mock->makePartial();

        $mock->shouldReceive('checkHasParentPromise')
            ->once()
            ->with($basePromise)
            ->andReturn(true);

        $mock->shouldReceive('checkJobsToDelete')
            ->never();

        $mock->checkPromiseToDelete($basePromise);

        $actualPromise = Promise::find($basePromise->getPromiseId());

        self::assertNotNull($actualPromise);
    }

    /**
     * @covers \Tochka\Promises\Core\GarbageCollector::checkHasParentPromise
     */
    public function testCheckHasParentPromise(): void
    {
        $parentPromise = new BasePromise(new TestPromise());
        Promise::saveBasePromise($parentPromise);

        $childPromise = new TestPromise();

        $baseJob = new BaseJob($parentPromise->getPromiseId(), $childPromise);
        PromiseJob::saveBaseJob($baseJob);

        $childPromise->setBaseJobId($baseJob->getJobId());
        $basePromise = new BasePromise($childPromise);
        Promise::saveBasePromise($basePromise);

        $gc = new GarbageCollector(0,0, []);

        $result = $gc->checkHasParentPromise($basePromise);

        self::assertTrue($result);
    }

    /**
     * @covers \Tochka\Promises\Core\GarbageCollector::checkHasParentPromise
     */
    public function testCheckHasParentPromiseNotPromised(): void
    {
        $childPromise = new TestPromise();
        $basePromise = new BasePromise($childPromise);
        Promise::saveBasePromise($basePromise);

        $gc = new GarbageCollector(0,0, []);

        $result = $gc->checkHasParentPromise($basePromise);

        self::assertFalse($result);
    }

    /**
     * @covers \Tochka\Promises\Core\GarbageCollector::checkHasParentPromise
     */
    public function testCheckHasParentPromiseNoParentJob(): void
    {
        $childPromise = new TestPromise();
        $childPromise->setBaseJobId(23);

        $basePromise = new BasePromise($childPromise);
        Promise::saveBasePromise($basePromise);

        $gc = new GarbageCollector(0,0, []);

        $result = $gc->checkHasParentPromise($basePromise);

        self::assertFalse($result);
    }

    /**
     * @covers \Tochka\Promises\Core\GarbageCollector::checkHasParentPromise
     */
    public function testCheckHasParentPromiseNoParentPromise(): void
    {
        $childPromise = new TestPromise();

        $baseJob = new BaseJob(23, $childPromise);
        PromiseJob::saveBaseJob($baseJob);

        $childPromise->setBaseJobId($baseJob->getJobId());
        $basePromise = new BasePromise($childPromise);
        Promise::saveBasePromise($basePromise);

        $gc = new GarbageCollector(0,0, []);

        $result = $gc->checkHasParentPromise($basePromise);

        self::assertFalse($result);
    }

    /**
     * @covers \Tochka\Promises\Core\GarbageCollector::checkJobsToDelete
     * @throws \Exception
     */
    public function testCheckJobsToDelete(): void
    {
        /** @var PromiseJob $promiseJob */
        $promiseJob = PromiseJob::factory()->create();
        $gc = new GarbageCollector(0,0, []);

        $gc->checkJobsToDelete($promiseJob->getBaseJob());

        $actualPromiseJob = PromiseJob::find($promiseJob->id);
        self::assertNull($actualPromiseJob);
    }

    /**
     * @covers \Tochka\Promises\Core\GarbageCollector::checkJobsToDelete
     * @throws \Exception
     */
    public function testCheckJobsToDeleteWaitEventAttached(): void
    {
        $waitEvent = new WaitEvent('Test', '1');

        $baseJob = new BaseJob(1, $waitEvent);
        PromiseJob::saveBaseJob($baseJob);

        $waitEvent->setBaseJobId($baseJob->getJobId());
        PromiseEvent::saveWaitEvent($waitEvent);

        $baseJob->setInitial($waitEvent);
        PromiseJob::saveBaseJob($baseJob);

        $gc = new GarbageCollector(0,0, []);

        $gc->checkJobsToDelete($baseJob);

        $actualPromiseJob = PromiseJob::find($baseJob->getJobId());
        $actualWaitEvent = PromiseEvent::find($waitEvent->getId());

        self::assertNull($actualPromiseJob);
        self::assertNull($actualWaitEvent);
    }

    /**
     * @covers \Tochka\Promises\Core\GarbageCollector::checkJobsToDelete
     * @throws \Exception
     */
    public function testCheckJobsToDeleteWaitEventNotAttached(): void
    {
        $waitEvent = new WaitEvent('Test', '1');

        $baseJob = new BaseJob(1, $waitEvent);
        PromiseJob::saveBaseJob($baseJob);

        $waitEvent->setBaseJobId($baseJob->getJobId());
        PromiseEvent::saveWaitEvent($waitEvent);
        $waitEvent->setAttachedModel(null);

        $baseJob->setInitial($waitEvent);
        PromiseJob::saveBaseJob($baseJob);

        $gc = new GarbageCollector(0,0, []);

        $gc->checkJobsToDelete($baseJob);

        $actualPromiseJob = PromiseJob::find($baseJob->getJobId());
        $actualWaitEvent = PromiseEvent::find($waitEvent->getId());

        self::assertNull($actualPromiseJob);
        self::assertNull($actualWaitEvent);
    }
}
