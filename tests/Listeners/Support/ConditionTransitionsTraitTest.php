<?php

namespace Tochka\Promises\Tests\Listeners\Support;

use Tochka\Promises\Contracts\ConditionContract;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Listeners\Support\ConditionTransitionsTrait;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestPromise;

/**
 * @covers \Tochka\Promises\Listeners\Support\ConditionTransitionsTrait
 */
class ConditionTransitionsTraitTest extends TestCase
{
    public function getTransitionForConditionsProvider(): array
    {
        $trueMock = \Mockery::mock(ConditionContract::class);
        $trueMock->shouldReceive('condition')
            ->withAnyArgs()
            ->andReturn(true);

        $falseMock = \Mockery::mock(ConditionContract::class);
        $falseMock->shouldReceive('condition')
            ->withAnyArgs()
            ->andReturn(false);

        $trueCondition = new ConditionTransition($trueMock, StateEnum::RUNNING(), StateEnum::SUCCESS());
        $falseCondition = new ConditionTransition($falseMock, StateEnum::WAITING(), StateEnum::SUCCESS());

        return [
            'None' => [
                [$falseCondition],
                null,
            ],
            'Yes'  => [
                [$trueCondition],
                $trueCondition,
            ],
        ];
    }

    /**
     * @dataProvider getTransitionForConditionsProvider
     * @covers       \Tochka\Promises\Listeners\Support\ConditionTransitionsTrait::getTransitionForConditions
     *
     * @param array<ConditionTransition> $conditions
     * @param ConditionTransition|null   $expected
     */
    public function testGetTransitionForConditions(array $conditions, ?ConditionTransition $expected): void
    {
        $basePromise = new BasePromise(new TestPromise());

        $listener = \Mockery::mock(ConditionTransitionsTrait::class);
        $result = $listener->getTransitionForConditions($conditions, $basePromise);

        self::assertEquals($expected, $result);
    }

    public function getConditionsForStateProvider(): array
    {
        $trueMock = \Mockery::mock(ConditionContract::class);
        $trueMock->shouldReceive('condition')
            ->withAnyArgs()
            ->andReturn(true);

        $falseMock = \Mockery::mock(ConditionContract::class);
        $falseMock->shouldReceive('condition')
            ->withAnyArgs()
            ->andReturn(false);

        $runningCondition = new ConditionTransition($trueMock, StateEnum::RUNNING(), StateEnum::SUCCESS());
        $waitingCondition = new ConditionTransition($trueMock, StateEnum::WAITING(), StateEnum::SUCCESS());
        $canceledCondition = new ConditionTransition($trueMock, StateEnum::CANCELED(), StateEnum::SUCCESS());

        return [
            'One'  => [
                StateEnum::RUNNING(),
                [
                    $runningCondition,
                    $waitingCondition,
                    $canceledCondition,
                ],
                [
                    $runningCondition,
                ],
            ],
            'More' => [
                StateEnum::RUNNING(),
                [
                    $runningCondition,
                    $waitingCondition,
                    $runningCondition,
                    $canceledCondition,
                ],
                [
                    $runningCondition,
                    $runningCondition,
                ],
            ],
            'None' => [
                StateEnum::SUCCESS(),
                [
                    $runningCondition,
                    $waitingCondition,
                    $canceledCondition,
                ],
                [],
            ],
        ];
    }

    /**
     * @dataProvider getConditionsForStateProvider
     * @covers       \Tochka\Promises\Listeners\Support\ConditionTransitionsTrait::getConditionsForState
     *
     * @param StateEnum                  $state
     * @param array<ConditionTransition> $conditions
     * @param array<ConditionTransition> $expected
     */
    public function testGetConditionsForState(StateEnum $state, array $conditions, array $expected): void
    {
        $basePromise = new BasePromise(new TestPromise());
        $basePromise->setState($state);
        $basePromise->setConditions($conditions);

        $listener = \Mockery::mock(ConditionTransitionsTrait::class);
        $result = $listener->getConditionsForState($basePromise, $basePromise);

        self::assertEqualsCanonicalizing($expected, $result);
    }
}
