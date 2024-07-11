<?php

namespace Tochka\Promises\Events;

use Tochka\Promises\Contracts\MayPromised;

/**
 * @api
 */
class PromiseJobStarted
{
    private MayPromised $job;

    public function __construct(MayPromised $job)
    {
        $this->job = $job;
    }

    public function getJob(): MayPromised
    {
        return $this->job;
    }
}
