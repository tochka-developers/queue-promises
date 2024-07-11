<?php

namespace Tochka\Promises\Contracts;

/**
 * @api
 */
interface CustomConnection
{
    public function getConnection(): ?string;
}
