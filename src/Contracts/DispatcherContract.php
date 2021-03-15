<?php

namespace Tochka\Promises\Contracts;

interface DispatcherContract
{
    public function mayDispatch(MayPromised $promised): bool;

    public function dispatch(MayPromised $promised): void;
}
