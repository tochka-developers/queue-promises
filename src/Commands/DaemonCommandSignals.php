<?php

namespace Tochka\Promises\Commands;

use Illuminate\Console\Command;

/**
 * @mixin Command
 */
trait DaemonCommandSignals
{
    private bool $shouldQuit = false;
    private bool $paused = false;

    protected function subscribeSignals(): void
    {
        $this->trap([SIGTERM, SIGQUIT, SIGINT], function () {
            $this->shouldQuit = true;
        });

        $this->trap([SIGUSR2], function () {
            $this->paused = true;
        });

        $this->trap([SIGCONT], function () {
            $this->paused = false;
        });
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
