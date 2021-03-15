<?php

namespace Tochka\Promises\Contracts;

use Tochka\Promises\Enums\StateEnum;

interface JobStateContract
{
    public function getState(): StateEnum;
}
