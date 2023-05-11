<?php

namespace Tochka\Promises\Core\Support;

use Carbon\Carbon;

trait DaemonWorker
{
    use DaemonWithSignals;

    private int $sleepTime;
    private Carbon $lastIteration;

    public function daemon(callable $callback): void
    {
        if ($this->supportsAsyncSignals()) {
            $this->listenForSignals();
        }

        while (true) {
            if ($this->shouldQuit()) {
                return;
            }

            if ($this->paused() || $this->sleepAfterLastIteration()) {
                $this->sleep(1);

                continue;
            }

            $callback();

            $this->lastIteration = Carbon::now();
        }
    }

    private function sleep(int|float $seconds): void
    {
        if ($seconds < 1) {
            usleep($seconds * 1000000);
        } else {
            sleep($seconds);
        }
    }

    private function sleepAfterLastIteration(): bool
    {
        return $this->lastIteration > Carbon::now()->subSeconds($this->sleepTime);
    }
}
