<?php

namespace Tochka\Promises\Core\Support;

use Carbon\Carbon;

trait DaemonWorker
{
    private int $sleepTime;
    private Carbon $lastIteration;

    /**
     * @param callable $callback
     * @param null|callable(): bool $shouldQuitCallback
     * @param null|callable(): bool $shouldPausedCallback
     * @return void
     */
    public function daemon(callable $callback, ?callable $shouldQuitCallback = null, ?callable $shouldPausedCallback = null): void
    {
        if ($shouldQuitCallback === null) {
            $shouldQuitCallback = fn() => false;
        }
        if ($shouldPausedCallback === null) {
            $shouldPausedCallback = fn() => false;
        }

        while (true) {
            if ($shouldQuitCallback()) {
                return;
            }

            if ($shouldPausedCallback() || $this->sleepAfterLastIteration()) {
                $this->sleep(1);

                continue;
            }

            $callback();

            $this->lastIteration = Carbon::now();
        }
    }

    private function sleep(int|float $seconds): void
    {
        if (is_float($seconds)) {
            $seconds = (int) $seconds * 1000000;
            if ($seconds < 0) {
                return;
            }

            usleep($seconds);
        } else {
            if ($seconds < 0) {
                return;
            }

            sleep($seconds);
        }
    }

    private function sleepAfterLastIteration(): bool
    {
        return $this->lastIteration > Carbon::now()->subSeconds($this->sleepTime);
    }
}
