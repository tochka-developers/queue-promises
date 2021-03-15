<?php

namespace Tochka\Promises\Tests\Conditions;

use PHPUnit\Framework\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestPromise;
use Tochka\Promises\Conditions\Positive;
use Tochka\Promises\Core\BasePromise;

class PositiveTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Conditions\Positive::condition
     */
    public function testCondition(): void
    {
        $condition = new Positive();
        $basePromise = new BasePromise(new TestPromise());
        $actualCondition = $condition->condition($basePromise);

        $this->assertEquals(true, $actualCondition);
    }
}
