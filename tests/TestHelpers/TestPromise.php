<?php

namespace Tochka\JsonRpc\Tests\TestHelpers;

use Tochka\Promises\Contracts\PromiseHandler;
use Tochka\Promises\Support\DefaultPromise;

class TestPromise implements PromiseHandler
{
    use DefaultPromise;
}