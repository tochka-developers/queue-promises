<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace Tochka\Promises\Commands;

use Illuminate\Console\Command;
use Tochka\Promises\Facades\PromiseWatcher;

/**
 * @codeCoverageIgnore
 */
class PromiseWatch extends Command
{
    use DaemonCommandSignals;

    protected $signature = 'promise:watch';

    protected $description = 'Смотритель промисов';

    public function handle(): void
    {
        $this->subscribeSignals();

        PromiseWatcher::watch($this->shouldQuit(...), $this->paused(...));
    }
}
