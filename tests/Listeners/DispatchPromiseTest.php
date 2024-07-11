<?php

namespace Tochka\Promises\Tests\Listeners;

use Illuminate\Support\Facades\Queue;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\Support\PromiseQueueJob;
use Tochka\Promises\Enums\StateEnum;
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
        Queue::fake();
        $promiseHandler = new TestPromise();
        $basePromise = new BasePromise($promiseHandler);
        $basePromise->setPromiseId(1);
        $basePromise->setState(StateEnum::SUCCESS());

        $event = new PromiseStateChanged($basePromise, StateEnum::RUNNING(), StateEnum::SUCCESS());

        $listener = new DispatchPromise();
        $listener->dispatchPromise($event);

        Queue::assertPushed(
            function (PromiseQueueJob $job) use ($basePromise) {
                self::assertEquals($basePromise->getPromiseId(), $job->getPromiseId());
                self::assertEquals($basePromise->getState(), $job->getState());
                self::assertEquals($basePromise->getPromiseHandler(), $job->getPromiseHandler());

                return true;
            },
        );
    }
}
