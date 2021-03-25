<?php

namespace Tochka\Promises\Tests\Support;

use Tochka\Promises\Support\BaseJobId;
use Tochka\Promises\Tests\TestCase;

/**
 * @covers \Tochka\Promises\Support\BaseJobId
 */
class BaseJobIdTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Support\BaseJobId::setBaseJobId
     * @covers \Tochka\Promises\Support\BaseJobId::getBaseJobId
     */
    public function testSetGetBaseJobId(): void
    {
        $expected = 234;
        $mock = \Mockery::mock(BaseJobId::class);

        self::assertNull($mock->getBaseJobId());

        $mock->setBaseJobId($expected);
        $result = $mock->getBaseJobId();

        self::assertEquals($expected, $result);
    }
}
