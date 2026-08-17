<?php

namespace Tochka\Promises\Core\Support;

use Carbon\Carbon;
use Carbon\CarbonImmutable;

trait DaemonWorker
{
    private int $sleepTime;
    private CarbonImmutable $lastIteration;

    /**
     * @param callable $callback
     * @param null|callable(): bool $shouldQuitCallback
     * @param null|callable(): bool $shouldPausedCallback
     * @return void
     */
    public function daemon(callable $callback, ?callable $shouldQuitCallback = null, ?callable $shouldPausedCallback = null): void
    {
        if ($shouldQuitCallback === null) {
            $shouldQuitCallback = fn(): bool => false;
        }
        if ($shouldPausedCallback === null) {
            $shouldPausedCallback = fn(): bool => false;
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

            $this->lastIteration = $this->startOfTime();
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

    private function startOfTime(): CarbonImmutable
    {
        return method_exists(CarbonImmutable::class, 'startOfTime')
            ? CarbonImmutable::startOfTime()
            : Carbon::minValue()->toImmutable();
    }
}
