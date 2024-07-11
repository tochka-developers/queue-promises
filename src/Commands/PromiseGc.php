<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace Tochka\Promises\Commands;

use Illuminate\Console\Command;
use Tochka\Promises\Core\GarbageCollectorInterface;

/**
 * @codeCoverageIgnore
 */
class PromiseGc extends Command
{
    use DaemonCommandSignals;

    protected $signature = 'promise:gc';
    protected $description = 'Сборщик мусора';

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function handle(GarbageCollectorInterface $garbageCollector): void
    {
        $this->subscribeSignals();

        $garbageCollector->handle($this->shouldQuit(...), $this->paused(...));
    }
}
