<?php

namespace Tochka\Promises\Tests\Listeners;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Event;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\Support\PromiseQueueJob;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\PromiseHandlerDispatched;
use Tochka\Promises\Events\PromiseHandlerDispatching;
use Tochka\Promises\Events\PromiseStateChanged;
use Tochka\Promises\Listeners\DispatchPromise;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestPromise;

/**
 * @covers \Tochka\Promises\Listeners\DispatchPromise
 */
class DispatchPromiseTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Listeners\DispatchPromise::dispatchPromise
     */
    public function testDispatchPromise(): void
    {
        Event::fake();

        $promiseHandler = new TestPromise();
        $basePromise = new BasePromise($promiseHandler);
        $basePromise->setPromiseId(1);
        $basePromise->setState(StateEnum::SUCCESS());

        $event = new PromiseStateChanged($basePromise, StateEnum::RUNNING(), StateEnum::SUCCESS());

        $dispatcher = \Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->withArgs(function (PromiseQueueJob $job) use ($basePromise) {
                self::assertEquals($basePromise->getPromiseId(), $job->getPromiseId());
                self::assertEquals($basePromise->getState(), $job->getState());
                self::assertEquals($basePromise->getPromiseHandler(), $job->getPromiseHandler());
                return true;
            });

        $listener = new DispatchPromise($dispatcher);
        $listener->dispatchPromise($event);

        Event::assertDispatched(PromiseHandlerDispatching::class);
        Event::assertDispatched(PromiseHandlerDispatched::class);
    }
}
