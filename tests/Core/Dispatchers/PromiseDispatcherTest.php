<?php

namespace Tochka\Promises\Tests\Core\Dispatchers;

use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Contracts\PromiseHandler;
use Tochka\Promises\Core\Dispatchers\PromiseDispatcher;
use Tochka\Promises\Tests\TestCase;

/**
 * @covers \Tochka\Promises\Core\Dispatchers\PromiseDispatcher
 */
class PromiseDispatcherTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Core\Dispatchers\PromiseDispatcher::dispatch
     */
    public function testDispatch(): void
    {
        $mockPromise = \Mockery::mock(PromiseHandler::class);
        $mockPromise->shouldReceive('run')
            ->once();

        $dispatcher = new PromiseDispatcher();
        $dispatcher->dispatch($mockPromise);
    }

    /**
     * @covers \Tochka\Promises\Core\Dispatchers\PromiseDispatcher::mayDispatch
     */
    public function testMayDispatch(): void
    {
        $dispatcher = new PromiseDispatcher();

        $mockPromise = \Mockery::mock(PromiseHandler::class);
        $result = $dispatcher->mayDispatch($mockPromise);
        self::assertTrue($result);

        $mockNotPromise = \Mockery::mock(MayPromised::class);
        $result = $dispatcher->mayDispatch($mockNotPromise);
        self::assertFalse($result);
    }
}
