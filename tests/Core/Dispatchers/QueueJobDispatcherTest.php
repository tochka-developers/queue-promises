<?php

namespace Tochka\Promises\Tests\Core\Dispatchers;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mockery\MockInterface;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Core\Dispatchers\QueueJobDispatcher;
use Tochka\Promises\Tests\TestCase;

/**
 * @covers \Tochka\Promises\Core\Dispatchers\QueueJobDispatcher
 */
class QueueJobDispatcherTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Core\Dispatchers\QueueJobDispatcher::dispatch
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testDispatch(): void
    {
        $mockQueue = \Mockery::mock(MayPromised::class, ShouldQueue::class);
        $dispatcher = new QueueJobDispatcher();

        $this->mock(
            Dispatcher::class,
            function (MockInterface $mock) {
                $mock->shouldReceive('dispatch')->once();
            }
        );

        $dispatcher->dispatch($mockQueue);
    }

    /**
     * @covers \Tochka\Promises\Core\Dispatchers\QueueJobDispatcher::mayDispatch
     */
    public function testMayDispatch(): void
    {
        $dispatcher = new QueueJobDispatcher();

        $mockQueue = \Mockery::mock(MayPromised::class, ShouldQueue::class);
        $result = $dispatcher->mayDispatch($mockQueue);
        self::assertTrue($result);

        $mockNotQueue = \Mockery::mock(MayPromised::class);
        $result = $dispatcher->mayDispatch($mockNotQueue);
        self::assertFalse($result);
    }
}
