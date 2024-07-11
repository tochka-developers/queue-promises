<?php

namespace Tochka\Promises\Core;

use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Contracts\PromiseHandler;

/**
 * @api
 */
interface PromiseRunnerInterface
{
    /**
     * @param array<MayPromised> $jobs
     */
    public function run(PromiseHandler $handler, array $jobs): void;
}
