<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace Tochka\Promises\Commands;

use Illuminate\Console\Command;
use Tochka\Promises\Facades\GarbageCollector;

/**
 * @codeCoverageIgnore
 */
class PromiseGc extends Command
{
    use DaemonCommandSignals;

    protected $signature = 'promise:gc';
    protected $description = 'Сборщик мусора';

    public function handle(): void
    {
        $this->subscribeSignals();

        GarbageCollector::handle($this->shouldQuit(...), $this->paused(...));
    }
}
