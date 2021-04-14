<?php

namespace Tochka\Promises\Core\Support;

trait DaemonWithSignals
{
    private bool $shouldQuit = false;
    private bool $paused = false;

    /**
     * Enable async signals for the process.
     *
     * @return void
     */
    protected function listenForSignals(): void
    {
        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, function () {
            echo 'GC daemon is terminating...' . PHP_EOL;
            $this->shouldQuit = true;
        });

        pcntl_signal(SIGUSR2, function () {
            echo 'GC daemon is paused...' . PHP_EOL;
            $this->paused = true;
        });

        pcntl_signal(SIGCONT, function () {
            echo 'GC daemon continued execution' . PHP_EOL;
            $this->paused = false;
        });
    }

    /**
     * Determine if "async" signals are supported.
     *
     * @return bool
     */
    protected function supportsAsyncSignals(): bool
    {
        return extension_loaded('pcntl');
    }

    protected function shouldQuit(): bool
    {
        return $this->shouldQuit;
    }

    protected function paused(): bool
    {
        return $this->paused;
    }
}
