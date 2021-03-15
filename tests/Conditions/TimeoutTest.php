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
    private Carbon $nowTime;

    public function setUp(): void
    {
        parent::setUp();

        $this->nowTime = Carbon::parse('2021-02-20T00:00:00');
    }

    public function conditionProvider(): array
    {
        $this->nowTime = Carbon::parse('2021-02-20T00:00:00');
        Carbon::setTestNow($this->nowTime);

        return [
            'Carbon true'        => [
                Carbon::now(),
                true,
            ],
            'Carbon false'       => [
                Carbon::now()->addMinutes(3),
                false,
            ],
            'DateInterval true'  => [
                CarbonInterval::minutes(1),
                true,
            ],
            'DateInterval false' => [
                CarbonInterval::minutes(3),
                false,
            ],
            'Int true'           => [
                1,
                true,
            ],
            'Int false'          => [
                3,
                false,
            ],
            'Parse true'         => [
                '+1 minute',
                true,
            ],
            'Parse false'        => [
                '+3 minute',
                false,
            ],
        ];
    }

    /**
     * @dataProvider conditionProvider
     * @covers       \Tochka\Promises\Conditions\Timeout::condition
     *
     * @param mixed $timeout
     * @param bool  $expected
     */
    public function testCondition($timeout, bool $expected): void
    {
        Carbon::setTestNow($this->nowTime);
        $basePromise = new BasePromise(new TestPromise());
        $condition = new Timeout($timeout);

        Carbon::setTestNow($this->nowTime->addMinutes(2));
        $result = $condition->condition($basePromise);

        self::assertEquals($expected, $result);
    }
}
