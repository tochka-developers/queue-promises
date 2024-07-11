<?php

namespace Tochka\Promises\Commands;

use Illuminate\Console\Command;
use Tochka\Promises\Core\GarbageCollectorInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PromiseClean extends Command
{
    use DaemonCommandSignals;

    protected $signature = 'promise:clean';
    protected $description = 'Собрать мусор и удалить';

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function handle(GarbageCollectorInterface $garbageCollector): void
    {
        $this->subscribeSignals();

        $garbageCollector->clean($this->shouldQuit(...), $this->paused(...));
    }
}
