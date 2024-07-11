<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace Tochka\Promises\Commands;

use Illuminate\Console\Command;
use Tochka\Promises\Core\PromiseWatcherInterface;

/**
 * @codeCoverageIgnore
 */
class PromiseWatch extends Command
{
    use DaemonCommandSignals;

    protected $signature = 'promise:watch';

    protected $description = 'Смотритель промисов';

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function handle(PromiseWatcherInterface $promiseWatcher): void
    {
        $this->subscribeSignals();

        $promiseWatcher->watch($this->shouldQuit(...), $this->paused(...));
    }
}
