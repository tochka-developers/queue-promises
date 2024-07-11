<?php

namespace Tochka\Promises\Core;

use PHPUnit\Framework\Assert as PHPUnit;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Contracts\PromiseHandler;

/**
 * @codeCoverageIgnore
 */
class FakePromiseRunner
{
    /** @var array<string, PromiseHandler> */
    private array $promises = [];
    /** @var array<string, array<MayPromised>> */
    private array $jobs = [];

    /**
     * @param PromiseHandler     $handler
     * @param array<MayPromised> $jobs
     */
    public function run(PromiseHandler $handler, array $jobs): void
    {
        $className = get_class($handler);
        $this->promises[$className] = $handler;
        $this->jobs[$className] = $jobs;
    }

    public function assertRun(string $promiseHandler): void
    {
        PHPUnit::assertArrayHasKey(
            $promiseHandler,
            $this->promises,
            sprintf('Check promise handler [%s] is run', $promiseHandler),
        );
    }

    public function assertNotRun(string $promiseHandler): void
    {
        PHPUnit::assertArrayNotHasKey(
            $promiseHandler,
            $this->promises,
            sprintf('Check promise handler [%s] is not run', $promiseHandler),
        );
    }

    public function assertAddedJobsCount(string $promiseHandler, int $expected): void
    {
        PHPUnit::assertArrayHasKey(
            $promiseHandler,
            $this->jobs,
            sprintf('Check promised job added count [%s]', $promiseHandler),
        );
        PHPUnit::assertCount(
            $expected,
            $this->jobs[$promiseHandler] ?? [],
            sprintf('Check promised job added count [%s]', $promiseHandler),
        );
    }

    public function assertAddedJobs(string $promiseHandler, array $expected): void
    {
        PHPUnit::assertArrayHasKey(
            $promiseHandler,
            $this->jobs,
            sprintf('Check promised job added [%s]', $promiseHandler),
        );
        PHPUnit::assertEquals(
            $expected,
            $this->jobs[$promiseHandler] ?? [],
            sprintf('Check promised job added [%s]', $promiseHandler),
        );
    }
}
