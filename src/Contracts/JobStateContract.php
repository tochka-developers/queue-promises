<?php

namespace Tochka\Promises\Contracts;

use Tochka\Promises\Enums\StateEnum;

interface JobStateContract
{
    /**
     * @return \Tochka\Promises\Enums\StateEnum
     */
    public function getState(): StateEnum;
}