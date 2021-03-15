<?php

namespace Tochka\Promises\Tests\TestHelpers;

use Tochka\Promises\Contracts\MayPromised;
use Tochka\Promises\Support\PromisedJob;

class TestJob implements MayPromised
{
    use PromisedJob;

    private string $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function handle(): string
    {
        return $this->message;
    }
}
