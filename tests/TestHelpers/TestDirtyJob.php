<?php

namespace Tochka\Promises\Tests\TestHelpers;

use Illuminate\Foundation\Application;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\SyncJob;
use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Support\PromisedJob;

class TestDirtyJob implements MayPromised
{
    use PromisedJob, InteractsWithQueue;

    private string $message;

    public function __construct(string $message)
    {
        $this->message = $message;
        $this->job = new SyncJob(Application::getInstance(), '', 'name', 'queue');
    }

    public function handle(): string
    {
        return $this->message;
    }
}
