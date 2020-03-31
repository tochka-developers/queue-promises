<?php

namespace Tochka\Promises\Contracts;

interface DispatcherContract
{
    /**
     * @param \Tochka\Promises\Contracts\MayPromised $promised
     *
     * @return bool
     */
    public function mayDispatch(MayPromised $promised): bool;

    /**
     * @param \Tochka\Promises\Contracts\MayPromised $promised
     *
     * @return mixed
     */
    public function dispatch(MayPromised $promised): void;
}