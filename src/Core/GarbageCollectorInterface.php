<?php

namespace Tochka\Promises\Core;

interface GarbageCollectorInterface
{
    /**
     * @param null|callable(): bool $shouldQuitCallback
     * @param null|callable(): bool $shouldPausedCallback
     */
    public function handle(?callable $shouldQuitCallback = null, ?callable $shouldPausedCallback = null): void;

    /**
     * @param null|callable(): bool $shouldQuitCallback
     * @param null|callable(): bool $shouldPausedCallback
     */
    public function clean(?callable $shouldQuitCallback = null, ?callable $shouldPausedCallback = null): void;
}
