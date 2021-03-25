<?php

namespace Tochka\Promises\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Contracts\PromiseHandler;
use Tochka\Promises\Core\FakePromiseRunner;

/**
 * @method static run(PromiseHandler $handler, MayPromised[] $jobs)
 * @method static assertRun(string $promiseHandler)
 * @method static assertNotRun(string $promiseHandler)
 * @method static assertAddedJobsCount(string $promiseHandler, int $expected)
 * @method static assertAddedJobs(string $promiseHandler, array $expected)
 * @see \Tochka\Promises\Core\FakePromiseRunner
 * @see \Tochka\Promises\Core\PromiseRunner
 * @codeCoverageIgnore
 */
class Promises extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::class;
    }

    /**
     * Replace the bound instance with a fake.
     *
     * @return FakePromiseRunner
     */
    public static function fake(): FakePromiseRunner
    {
        static::swap($fake = new FakePromiseRunner());

        return $fake;
    }

    /**
     * Replace the bound instance with a fake during the given callable's execution.
     *
     * @param callable $callable
     *
     * @return callable
     */
    public static function fakeFor(callable $callable)
    {
        $originalDispatcher = static::getFacadeRoot();

        static::fake();

        return tap(
            $callable(),
            function () use ($originalDispatcher) {
                static::swap($originalDispatcher);
            }
        );
    }
}
