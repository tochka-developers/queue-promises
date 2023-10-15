<?php

namespace Tochka\Promises\Commands;

use Illuminate\Console\Command;
use Tochka\Promises\Facades\GarbageCollector;

class PromiseClean extends Command
{
    use DaemonCommandSignals;

    protected $signature = 'promise:clean';
    protected $description = 'Собрать мусор и удалить';

    public function handle(): void
    {
        $this->subscribeSignals();

        GarbageCollector::clean($this->shouldQuit(...), $this->paused(...));
    }
}
