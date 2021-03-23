<?php

namespace Tochka\Promises\Tests\Core\Support;

use Tochka\Promises\Conditions\Positive;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Exceptions\IncorrectResolvingClass;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestJob;

/**
 * @covers \Tochka\Promises\Core\Support\ConditionTransition
 */
class ConditionTransitionTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Core\Support\ConditionTransition::__construct
     * @covers \Tochka\Promises\Core\Support\ConditionTransition::getCondition
     * @covers \Tochka\Promises\Core\Support\ConditionTransition::getFromState
     * @covers \Tochka\Promises\Core\Support\ConditionTransition::getToState
     */
    public function testGetters(): void
    {
        $condition = new Positive();
        $conditionTransition = new ConditionTransition($condition, StateEnum::WAITING(), StateEnum::RUNNING());

        self::assertEquals($condition, $conditionTransition->getCondition());
        self::assertEquals(StateEnum::WAITING(), $conditionTransition->getFromState());
        self::assertEquals(StateEnum::RUNNING(), $conditionTransition->getToState());
    }

    /**
     * @covers \Tochka\Promises\Core\Support\ConditionTransition::toArray
     */
    public function testToArray(): void
    {
        $condition = new Positive();
        $expected = [
            'condition'  => serialize($condition),
            'from_state' => StateEnum::WAITING()->value,
            'to_state'   => StateEnum::RUNNING()->value,
        ];
        $conditionTransition = new ConditionTransition($condition, StateEnum::WAITING(), StateEnum::RUNNING());

        $array = $conditionTransition->toArray();

        self::assertEquals($expected, $array);
    }

    /**
     * @covers \Tochka\Promises\Core\Support\ConditionTransition::fromArray
     */
    public function testFromArraySuccess(): void
    {
        $condition = new Positive();
        $array = [
            'condition'  => serialize($condition),
            'from_state' => StateEnum::WAITING()->value,
            'to_state'   => StateEnum::RUNNING()->value,
        ];

        $conditionTransition = ConditionTransition::fromArray($array);

        self::assertEquals($condition, $conditionTransition->getCondition());
        self::assertEquals(StateEnum::WAITING(), $conditionTransition->getFromState());
        self::assertEquals(StateEnum::RUNNING(), $conditionTransition->getToState());
    }

    /**
     * @covers \Tochka\Promises\Core\Support\ConditionTransition::fromArray
     */
    public function testFromArrayEmptyKeys(): void
    {
        $condition = new Positive();
        $array = [
            'condition'  => serialize($condition),
            'from_state' => StateEnum::WAITING()->value,
        ];

        $this->expectException(IncorrectResolvingClass::class);
        ConditionTransition::fromArray($array);
    }

    /**
     * @covers \Tochka\Promises\Core\Support\ConditionTransition::fromArray
     */
    public function testFromArrayIncorrectClass(): void
    {
        $array = [
            'condition'  => serialize(new TestJob('exc')),
            'from_state' => StateEnum::WAITING()->value,
            'to_state'   => StateEnum::RUNNING()->value,
        ];

        $this->expectException(IncorrectResolvingClass::class);
        ConditionTransition::fromArray($array);
    }
}
