<?php

namespace Tochka\Promises\Tests\Support;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Support\ExpiredAt;
use Tochka\Promises\Support\Timeout;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestPromise;

/**
 * @covers \Tochka\Promises\Support\Timeout
 */
class TimeoutTest extends TestCase
{

    /**
     * @covers \Tochka\Promises\Support\Timeout::promiseConditionsTimeout
     */
    public function testPromiseConditionsTimeout(): void
    {
        Carbon::setTestNow(Carbon::now());

        $expected = Carbon::now()->addMinute();

        $basePromise = new BasePromise(new TestPromise());
        $mock = \Mockery::mock(Timeout::class);
        $mock->promiseConditionsTimeout($basePromise);

        self::assertCount(0, $basePromise->getConditions());

        $mock->setTimeout(CarbonInterval::minute());
        $mock->promiseConditionsTimeout($basePromise);

        $conditions = $basePromise->getConditions();
        self::assertCount(1, $conditions);

        foreach ($conditions as $conditionTransition) {
            $condition = $conditionTransition->getCondition();
            if ($condition instanceof \Tochka\Promises\Conditions\Timeout) {
                self::assertEquals(StateEnum::RUNNING(), $conditionTransition->getFromState());
                self::assertEquals(StateEnum::TIMEOUT(), $conditionTransition->getToState());
                self::assertEquals($expected, $condition->getExpiredAt());
            } else {
                self::fail('Unknown condition');
            }
        }

        self::assertEquals($expected, $basePromise->getTimeoutAt());
    }

    /**
     * @covers \Tochka\Promises\Support\Timeout::setTimeout
     * @covers \Tochka\Promises\Support\Timeout::getTimeout
     */
    public function testSetGetTimeout(): void
    {
        $expected = CarbonInterval::minute();
        $expected2 = CarbonInterval::minutes(2);

        $mock = \Mockery::mock(Timeout::class);

        self::assertNull($mock->getTimeout());

        $mock->setTimeout($expected);
        $result = $mock->getTimeout();

        self::assertEquals($expected, $result);

        // проверяем, что значение поля из класса приоритетнее значения поля в трейте
        $mock->timeout = $expected2;

        $result = $mock->getTimeout();

        self::assertEquals($expected2, $result);
    }
}
