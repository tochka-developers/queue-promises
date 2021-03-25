<?php

namespace Tochka\Promises\Tests\Support;

use Tochka\Promises\Support\PromisedJob;
use Tochka\Promises\Tests\TestCase;

/**
 * @covers \Tochka\Promises\Support\PromisedJob
 */
class PromisedJobTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Support\PromisedJob::setBaseJobId
     * @covers \Tochka\Promises\Support\PromisedJob::getBaseJobId
     */
    public function testSetGetBaseJobId(): void
    {
        $expected = 234;
        $mock = \Mockery::mock(PromisedJob::class);

        self::assertNull($mock->getBaseJobId());

        $mock->setBaseJobId($expected);
        $result = $mock->getBaseJobId();

        self::assertEquals($expected, $result);
    }
}
