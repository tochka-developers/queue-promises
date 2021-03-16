<?php

namespace Tochka\Promises\Tests\TestHelpers;

use Tochka\Promises\Listeners\Support\FilterTransitionsTrait;

class TestFilterListener
{
    use FilterTransitionsTrait;

    public array $transitions = [];

    public function test($event): void
    {

    }

    public function test2($event): void
    {

    }
}
