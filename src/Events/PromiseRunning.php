<?php

namespace Tochka\Promises\Events;

use Tochka\Promises\Contracts\PromiseHandler;

/**
 * @api
 */
class PromiseRunning
{
    private PromiseHandler $promiseHandler;

    public function __construct(PromiseHandler $promise)
    {
        $this->promiseHandler = $promise;
    }

    public function getPromiseHandler(): PromiseHandler
    {
        return $this->promiseHandler;
    }
}
