<?php


namespace Tochka\Promises\Commands;

use Illuminate\Console\Command;
use Tochka\Promises\Facades\PromiseWatcher;

class PromiseWatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promise:watch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Смотрить промисов';

    /**
     * Выполнить отправку
     *
     * @throws \Exception
     */
    public function handle(): void
    {
        PromiseWatcher::watch();
    }
}