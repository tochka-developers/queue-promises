<?php

namespace Tochka\Promises\Contracts;

use Tochka\Promises\Enums\StateEnum;

interface StateChangedContract
{
    public function getFromState(): StateEnum;

    public function getToState(): StateEnum;
}
