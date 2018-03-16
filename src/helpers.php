<?php

if (!function_exists('dispatchSync')) {

    /**
     * @param \Tochka\Queue\Promises\Contracts\MayPromised[] $jobs
     * @param \Tochka\Queue\Promises\Jobs\Promise $promise
     */
    function dispatchSync($jobs, \Tochka\Queue\Promises\Jobs\Promise $promise)
    {
        foreach ($jobs as $job) {
            $promise->add($job);
        }

        $promise->runSync();
    }
}

if (!function_exists('dispatchAsync')) {

    /**
     * @param \Tochka\Queue\Promises\Contracts\MayPromised[] $jobs
     * @param \Tochka\Queue\Promises\Jobs\Promise $promise
     */
    function dispatchAsync($jobs, \Tochka\Queue\Promises\Jobs\Promise $promise)
    {
        foreach ($jobs as $job) {
            $promise->add($job);
        }

        $promise->runAsync();
    }
}