<?php

namespace Tochka\Promises\Tests\Conditions;

use Tochka\Promises\Conditions\Positive;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestPromise;

/**
 * @covers \Tochka\Promises\Conditions\Positive
 */
class PositiveTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Conditions\Positive::condition
     */
    public function testCondition(): void
    {
        $basePromise = new BasePromise(new TestPromise());

        $condition = new Positive();
        $actualCondition = $condition->condition($basePromise);

        self::assertEquals(true, $actualCondition);
    }
}
