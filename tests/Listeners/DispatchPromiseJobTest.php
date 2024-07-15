<?php

namespace Tochka\Promises\Tests\Listeners;

use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\Support\BaseJobDispatcherInterface;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\PromiseJobStateChanged;
use Tochka\Promises\Listeners\DispatchPromiseJob;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestJob;

/**
 * @covers \Tochka\Promises\Listeners\DispatchPromiseJob
 */
class DispatchPromiseJobTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Listeners\DispatchPromiseJob::dispatchJob
     */
    public function testDispatchJob(): void
    {
        $handleJob = new TestJob('initial');
        $baseJob = new BaseJob(1, $handleJob);
        $event = new PromiseJobStateChanged($baseJob, StateEnum::WAITING(), StateEnum::RUNNING());

        $baseJobDispatcher = \Mockery::mock(BaseJobDispatcherInterface::class);
        $baseJobDispatcher->shouldReceive('dispatch')
            ->once()
            ->with($handleJob);

        $listener = new DispatchPromiseJob($baseJobDispatcher);
        $listener->dispatchJob($event);
    }
}
