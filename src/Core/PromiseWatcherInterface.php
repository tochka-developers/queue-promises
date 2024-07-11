<?php

namespace Tochka\Promises\Core;

interface PromiseWatcherInterface
{
    /**
     * @param null|callable(): bool $shouldQuitCallback
     * @param null|callable(): bool $shouldPausedCallback
     */
    public function watch(?callable $shouldQuitCallback = null, ?callable $shouldPausedCallback = null): void;

    /**
     * @param callable(): bool $shouldQuitCallback
     * @param callable(): bool $shouldPausedCallback
     * @return void
     */
    public function watchIteration(callable $shouldQuitCallback, callable $shouldPausedCallback): void;
}
