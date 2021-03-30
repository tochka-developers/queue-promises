<?php

namespace Tochka\Promises\Tests\TestHelpers;

use Tochka\Promises\Contracts\PromiseHandler;
use Tochka\Promises\Support\DefaultPromise;

class TestPromise implements PromiseHandler
{
    use DefaultPromise;
}
