<?php

namespace Tochka\Promises;

use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Contracts\PromiseHandler;

class PromiseExecutor
{
    public function addJobToPromise(PromiseHandler $promiseHandler, MayPromised $promisedJob)
    {

    }

    public function run(Promise $promise)
    {
        $promise->setTransition();
    }
}
