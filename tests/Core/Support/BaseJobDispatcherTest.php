<?php

namespace Tochka\Promises\Tests\Core\Support;

use Tochka\Promises\Contracts\DispatcherContract;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Core\Support\BaseJobDispatcher;
use Tochka\Promises\Tests\TestCase;

/**
 * @covers \Tochka\Promises\Core\Support\BaseJobDispatcher
 */
class BaseJobDispatcherTest extends TestCase
{

    /**
     * @covers \Tochka\Promises\Core\Support\BaseJobDispatcher::addDispatcher
     * @covers \Tochka\Promises\Core\Support\BaseJobDispatcher::dispatch
     */
    public function testDispatch(): void
    {
        $mockMayPromised = \Mockery::mock(MayPromised::class);

        /** @var DispatcherContract|\Mockery\Mock $mockDispatcherTrue */
        $mockDispatcherTrue = \Mockery::mock('TrueDispatcher', DispatcherContract::class);
        $mockDispatcherTrue->shouldReceive('mayDispatch')
            ->once()
            ->with($mockMayPromised)
            ->andReturn(true);
        $mockDispatcherTrue->shouldReceive('dispatch')
            ->once()
            ->with($mockMayPromised);

        /** @var DispatcherContract|\Mockery\Mock $mockDispatcherFalse */
        $mockDispatcherFalse = \Mockery::mock('FalseDispatcher', DispatcherContract::class);
        $mockDispatcherFalse->shouldReceive('mayDispatch')
            ->once()
            ->with($mockMayPromised)
            ->andReturn(false);
        $mockDispatcherFalse->shouldReceive('dispatch')
            ->never()
            ->with($mockMayPromised);

        $dispatcher = new BaseJobDispatcher();
        $dispatcher->addDispatcher($mockDispatcherTrue);
        $dispatcher->addDispatcher($mockDispatcherFalse);

        $dispatcher->dispatch($mockMayPromised);
    }

}
