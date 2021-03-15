<?php

namespace Tochka\Promises\Tests\Conditions;

use Tochka\Promises\Conditions\OrConditions;
use Tochka\Promises\Contracts\ConditionContract;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestPromise;

/**
 * @covers \Tochka\Promises\Conditions\OrConditions
 */
class OrConditionsTest extends TestCase
{
    public function conditionProvider(): array
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
                true,
            ],
        ];
    }

    /**
     * @dataProvider conditionProvider
     * @covers       \Tochka\Promises\Conditions\OrConditions::condition
     * @covers       \Tochka\Promises\Conditions\OrConditions::addCondition
     *
     * @param array<ConditionContract> $conditions
     * @param bool                     $expected
     */
    public function testCondition(array $conditions, bool $expected): void
    {
        $basePromise = new BasePromise(new TestPromise());
        $condition = new OrConditions();
        foreach ($conditions as $internalCondition) {
            $condition->addCondition($internalCondition);
        }
        $result = $condition->condition($basePromise);

        self::assertEquals($expected, $result);
    }
}
