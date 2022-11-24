<?php

namespace Tochka\Promises\Tests\Listeners;

use Illuminate\Support\Facades\Log;
use Tochka\Promises\Contracts\StatesContract;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\StateChanged;
use Tochka\Promises\Listeners\LogStateChanged;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestJob;
use Tochka\Promises\Tests\TestHelpers\TestPromise;

/**
 * @covers \Tochka\Promises\Listeners\LogStateChanged
 */
class LogStateChangedTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Listeners\LogStateChanged::handle
     */
    public function testHandleBasePromise(): void
    {
        $promise = new BasePromise(new TestPromise());
        $promise->setPromiseId(1);

        $expectedContext = [
            'from_state'      => StateEnum::RUNNING()->value,
            'to_state'        => StateEnum::SUCCESS()->value,
            'promise_handler' => get_class($promise->getPromiseHandler()),
            'promise_id'      => $promise->getPromiseId(),
        ];

        $event = new StateChanged($promise, StateEnum::RUNNING(), StateEnum::SUCCESS());
        $listener = new LogStateChanged();

        Log::shouldReceive('debug')
            ->once()
            ->with(\Mockery::type('string'), \Mockery::subset($expectedContext))
            ->andReturn();

        $listener->handle($event);
    }

    /**
     * @covers \Tochka\Promises\Listeners\LogStateChanged::handle
     */
    public function testHandleBaseJob(): void
    {
        $job = new BaseJob(1, new TestJob('initial'));
        $job->setJobId(1);

        $expectedContext = [
            'from_state'  => StateEnum::RUNNING()->value,
            'to_state'    => StateEnum::SUCCESS()->value,
            'promise_id'  => $job->getPromiseId(),
            'job_handler' => get_class($job->getInitialJob()),
            'job_id'      => $job->getJobId(),
        ];

        $event = new StateChanged($job, StateEnum::RUNNING(), StateEnum::SUCCESS());
        $listener = new LogStateChanged();

        Log::shouldReceive('debug')
            ->once()
            ->with(\Mockery::type('string'), \Mockery::subset($expectedContext))
            ->andReturn();

        $listener->handle($event);
    }

    /**
     * @covers \Tochka\Promises\Listeners\LogStateChanged::handle
     */
    public function testHandleBaseAnother(): void
    {
        $expectedContext = [
            'from_state'  => StateEnum::RUNNING()->value,
            'to_state'    => StateEnum::SUCCESS()->value,
        ];

        $stateInstance = \Mockery::mock(StatesContract::class);
        $listener = new LogStateChanged();

        $event = new StateChanged($stateInstance, StateEnum::RUNNING(), StateEnum::SUCCESS());

        Log::shouldReceive('debug')
            ->once()
            ->with(\Mockery::type('string'), \Mockery::subset($expectedContext))
            ->andReturn();

        $listener->handle($event);
    }
}
