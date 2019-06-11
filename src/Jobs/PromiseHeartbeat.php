<?php

namespace Tochka\Queue\Promises\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class PromiseHeartbeat implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public $promise_id;

    public function __construct(Promise $promise)
    {
        $this->promise_id = $promise->promise_id;
        $this->queue = config('promises.timeout_queue', 'default');
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        Promise::promiseHeartbeat($this->promise_id);
    }
}