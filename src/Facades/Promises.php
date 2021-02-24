<?php

namespace Tochka\Promises\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\Promises\Core\FakePromiseRunner;

/**
 * @method static run(\Tochka\Promises\Contracts\PromiseHandler $handler, \Tochka\Promises\Contracts\MayPromised[] $jobs)
 * @method static assertRun(string $promiseHandler)
 * @method static assertNotRun(string $promiseHandler)
 * @method static assertAddedJobsCount(string $promiseHandler, int $expected)
 * @method static assertAddedJobs(string $promiseHandler, array $expected)
 * @see \Tochka\Promises\Core\FakePromiseRunner
 * @see \Tochka\Promises\Core\PromiseRunner
 */
class Promises extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @return FakePromiseRunner
     */
    public static function fake()
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

        return tap($callable(), function () use ($originalDispatcher) {
            static::swap($originalDispatcher);
        });
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return self::class;
    }
}
