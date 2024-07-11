<?php

namespace Tochka\Promises\Contracts;

/**
 * @api
 */
interface CustomQueue
{
    public function getQueue(): ?string;
}
