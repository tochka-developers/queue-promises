<?php

namespace Tochka\Promises\Facades;

use Illuminate\Support\Facades\Facade;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Contracts\PromiseHandler;
use Tochka\Promises\Core\FakePromiseRunner;
use Tochka\Promises\Core\PromiseRunnerInterface;

/**
 * @api
 * @method static void run(PromiseHandler $handler, MayPromised[] $jobs)
 * @method static void hookTraitsMethod(PromiseHandler $handler, string $methodName, ...$args)
 * @method static void assertRun(string $promiseHandler)
 * @method static void assertNotRun(string $promiseHandler)
 * @method static void assertAddedJobsCount(string $promiseHandler, int $expected)
 * @method static void assertAddedJobs(string $promiseHandler, array $expected)
 * @see FakePromiseRunner
 * @see PromiseRunnerInterface
 * @codeCoverageIgnore
 */
class Promises extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PromiseRunnerInterface::class;
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
            },
        );
    }
}
