<?php

namespace Tochka\Promises\Tests\Conditions;

use Tochka\Promises\Conditions\AndConditions;
use Tochka\Promises\Contracts\ConditionContract;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestPromise;

/**
 * @covers \Tochka\Promises\Conditions\AndConditions
 */
class AndConditionsTest extends TestCase
{
    public static function conditionProvider(): array
    {
        $trueMock = \Mockery::mock(ConditionContract::class);
        $trueMock->shouldReceive('condition')
            ->withAnyArgs()
            ->andReturn(true);

        $falseMock = \Mockery::mock(ConditionContract::class);
        $falseMock->shouldReceive('condition')
            ->withAnyArgs()
            ->andReturn(false);

        return [
            'AllTrue'      => [
                [$trueMock, $trueMock, $trueMock],
                true,
            ],
            'AllFalse'     => [
                [$falseMock, $falseMock, $falseMock],
                false,
            ],
            'TrueAndFalse' => [
                [$trueMock, $falseMock, $trueMock],
                false,
            ],
        ];
    }

    /**
     * @dataProvider conditionProvider
     * @covers       \Tochka\Promises\Conditions\AndConditions::condition
     * @covers       \Tochka\Promises\Conditions\AndConditions::addCondition
     *
     * @param array<ConditionContract> $conditions
     * @param bool                     $expected
     */
    public function testCondition(array $conditions, bool $expected): void
    {
        $basePromise = new BasePromise(new TestPromise());
        $condition = new AndConditions();
        foreach ($conditions as $internalCondition) {
            $condition->addCondition($internalCondition);
        }
        $result = $condition->condition($basePromise);

        self::assertEquals($expected, $result);
    }
}
