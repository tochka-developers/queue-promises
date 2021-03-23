<?php

namespace Tochka\Promises\Tests\Core\Support;

use Carbon\Carbon;
use Tochka\Promises\Core\Support\Time;
use Tochka\Promises\Tests\TestCase;

/**
 * @covers \Tochka\Promises\Core\Support\Time
 */
class TimeTest extends TestCase
{

    /**
     * @covers \Tochka\Promises\Core\Support\Time::getCreatedAt
     * @covers \Tochka\Promises\Core\Support\Time::setCreatedAt
     */
    public function testGetSetCreatedAt(): void
    {
        $expected = Carbon::now();

        $mock = \Mockery::mock(Time::class);
        $mock->setCreatedAt($expected);
        $result = $mock->getCreatedAt();

        self::assertEquals($expected, $result);
    }

    /**
     * @covers \Tochka\Promises\Core\Support\Time::getUpdatedAt
     * @covers \Tochka\Promises\Core\Support\Time::setUpdatedAt
     */
    public function testGetSetUpdatedAt(): void
    {
        $expected = Carbon::now();

        $mock = \Mockery::mock(Time::class);
        $mock->setUpdatedAt($expected);
        $result = $mock->getUpdatedAt();

        self::assertEquals($expected, $result);
    }
}
