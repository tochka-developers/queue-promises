<?php

namespace Tochka\Promises\Tests\Conditions;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Tochka\Promises\Conditions\Timeout;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestPromise;

/**
 * @covers \Tochka\Promises\Conditions\Timeout
 */
class TimeoutTest extends TestCase
{
    private const NOW_TIME = '2021-02-20T00:00:00+0000';

    public static function conditionProvider(): array
    {
        $nowTime = Carbon::parse(self::NOW_TIME);
        Carbon::setTestNow($nowTime);

        return [
            'Carbon true'        => [
                Carbon::now(),
                Carbon::now(),
                true,
            ],
            'Carbon false'       => [
                Carbon::now()->addMinutes(3),
                Carbon::now()->addMinutes(3),
                false,
            ],
            'DateInterval true'  => [
                CarbonInterval::minutes(1),
                Carbon::now()->addMinutes(1),
                true,
            ],
            'DateInterval false' => [
                CarbonInterval::minutes(3),
                Carbon::now()->addMinutes(3),
                false,
            ],
            'Int true'           => [
                1,
                Carbon::now()->addMinutes(1),
                true,
            ],
            'Int false'          => [
                3,
                Carbon::now()->addMinutes(3),
                false,
            ],
            'Parse true'         => [
                '+1 minute',
                Carbon::now()->addMinutes(1),
                true,
            ],
            'Parse false'        => [
                '+3 minute',
                Carbon::now()->addMinutes(3),
                false,
            ],
        ];
    }

    /**
     * @dataProvider conditionProvider
     * @covers       \Tochka\Promises\Conditions\Timeout::condition
     * @covers       \Tochka\Promises\Conditions\Timeout::getExpiredAt
     *
     * @param mixed          $timeout
     * @param \Carbon\Carbon $expiredAt
     * @param bool           $expected
     */
    public function testCondition($timeout, Carbon $expiredAt, bool $expected): void
    {
        $nowTime = Carbon::parse(self::NOW_TIME);
        Carbon::setTestNow($nowTime);
        $basePromise = new BasePromise(new TestPromise());
        $condition = new Timeout($timeout);
        $expiredAtResult = $condition->getExpiredAt();
        self::assertEquals($expiredAt, $expiredAtResult);

        Carbon::setTestNow($nowTime->addMinutes(2));
        $result = $condition->condition($basePromise);

        self::assertEquals($expected, $result);
    }
}
