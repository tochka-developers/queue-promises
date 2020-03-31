<?php

namespace Tochka\JsonRpc\Tests\Support;

use PHPUnit\Framework\TestCase;
use Tochka\JsonRpc\Tests\TestHelpers\TestPromise;
use Tochka\Promises\Conditions\Positive;
use Tochka\Promises\Core\BasePromise;

class PositiveTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Conditions\Positive::condition
     */
    public function testSetControllerSuffix(): void
    {
        $condition = new Positive();
        $basePromise = new BasePromise(new TestPromise());
        $actualCondition = $condition->condition($basePromise);

        $this->assertEquals(true, $actualCondition);
    }
}
