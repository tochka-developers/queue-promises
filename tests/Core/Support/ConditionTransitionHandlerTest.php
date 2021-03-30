<?php

namespace Tochka\Promises\Tests\Core\Support;

use Tochka\Promises\Contracts\ConditionContract;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Core\Support\ConditionTransitionHandler;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestPromise;

/**
 * @covers \Tochka\Promises\Core\Support\ConditionTransitionHandler
 */
class ConditionTransitionHandlerTest extends TestCase
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
     * @covers       \Tochka\Promises\Core\Support\ConditionTransitionHandler::getTransitionForConditions
     *
     * @param array<ConditionTransition> $conditions
     * @param ConditionTransition|null   $expected
     */
    public function testGetTransitionForConditions(array $conditions, ?ConditionTransition $expected): void
    {
        $basePromise = new BasePromise(new TestPromise());

        $handler = new ConditionTransitionHandler();
        $result = $handler->getTransitionForConditions($conditions, $basePromise);

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
     * @covers       \Tochka\Promises\Core\Support\ConditionTransitionHandler::getConditionsForState
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

        $handler = new ConditionTransitionHandler();
        $result = $handler->getConditionsForState($basePromise, $basePromise);

        self::assertEqualsCanonicalizing($expected, $result);
    }

    /**
     * @covers \Tochka\Promises\Core\Support\ConditionTransitionHandler::checkConditionAndApplyTransition
     */
    public function testCheckConditionAndApplyTransition(): void
    {
        $basePromise = new BasePromise(new TestPromise());

        $trueConditionMock = \Mockery::mock(ConditionContract::class);
        $trueConditionMock->shouldReceive('condition')
            ->andReturn(true);

        $conditionTransition = new ConditionTransition($trueConditionMock, StateEnum::WAITING(), StateEnum::RUNNING());

        $mock = \Mockery::mock(ConditionTransitionHandler::class);
        $mock->makePartial();

        $mock->shouldReceive('getConditionsForState')
            ->once()
            ->with($basePromise, $basePromise)
            ->andReturn([$conditionTransition]);

        $mock->shouldReceive('getTransitionForConditions')
            ->once()
            ->with([$conditionTransition], $basePromise)
            ->andReturn($conditionTransition);

        $result = $mock->checkConditionAndApplyTransition($basePromise, $basePromise, $basePromise);

        self::assertTrue($result);
        self::assertEquals(StateEnum::RUNNING(), $basePromise->getState());
    }

    /**
     * @covers \Tochka\Promises\Core\Support\ConditionTransitionHandler::checkConditionAndApplyTransition
     */
    public function testCheckConditionAndApplyTransitionFalse(): void
    {
        $basePromise = new BasePromise(new TestPromise());

        $falseConditionMock = \Mockery::mock(ConditionContract::class);
        $falseConditionMock->shouldReceive('condition')
            ->andReturn(false);

        $conditionTransition = new ConditionTransition($falseConditionMock, StateEnum::WAITING(), StateEnum::RUNNING());

        $mock = \Mockery::mock(ConditionTransitionHandler::class);
        $mock->makePartial();

        $mock->shouldReceive('getConditionsForState')
            ->once()
            ->with($basePromise, $basePromise)
            ->andReturn([$conditionTransition]);

        $mock->shouldReceive('getTransitionForConditions')
            ->once()
            ->with([$conditionTransition], $basePromise)
            ->andReturn(null);

        $result = $mock->checkConditionAndApplyTransition($basePromise, $basePromise, $basePromise);

        self::assertFalse($result);
        self::assertEquals(StateEnum::WAITING(), $basePromise->getState());
    }
}
