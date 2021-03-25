<?php

namespace Tochka\Promises\Tests\Support;

use Carbon\Carbon;
use Tochka\Promises\Conditions\Timeout;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Support\ExpiredAt;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestPromise;

/**
 * @covers \Tochka\Promises\Support\ExpiredAt
 */
class ExpiredAtTest extends TestCase
{

    /**
     * @covers \Tochka\Promises\Support\ExpiredAt::promiseConditionsExpiredAt
     */
    public function testPromiseConditionsExpiredAt(): void
    {
        $expected = Carbon::now();
        $basePromise = new BasePromise(new TestPromise());
        $mock = \Mockery::mock(ExpiredAt::class);
        $mock->promiseConditionsExpiredAt($basePromise);

        self::assertCount(0, $basePromise->getConditions());

        $mock->setExpiredAt($expected);
        $mock->promiseConditionsExpiredAt($basePromise);

        $conditions = $basePromise->getConditions();
        self::assertCount(1, $conditions);

        foreach ($conditions as $conditionTransition) {
            $condition = $conditionTransition->getCondition();
            if ($condition instanceof Timeout) {
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
     * @covers \Tochka\Promises\Support\ExpiredAt::setExpiredAt
     * @covers \Tochka\Promises\Support\ExpiredAt::getExpiredAt
     */
    public function testSetGetExpiredAt(): void
    {
        $expected = Carbon::now();
        $expected2 = Carbon::now()->addMinute();

        $mock = \Mockery::mock(ExpiredAt::class);

        self::assertNull($mock->getExpiredAt());

        $mock->setExpiredAt($expected);
        $result = $mock->getExpiredAt();

        self::assertEquals($expected, $result);

        // проверяем, что значение поля из класса приоритетнее значения поля в трейте
        $mock->expired_at = $expected2;

        $result = $mock->getExpiredAt();

        self::assertEquals($expected2, $result);
    }
}
