<?php

namespace Tochka\Promises\Core\Support;

use Tochka\Promises\Contracts\DispatcherContract;
use Tochka\Promises\Contracts\MayPromised;

interface BaseJobDispatcherInterface
{
    public function addDispatcher(DispatcherContract $dispatcher): void;

    public function dispatch(MayPromised $job): void;
}
