<?php

namespace Tochka\Promises\Tests\Core\Support;

use Tochka\Promises\Conditions\Positive;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Core\Support\ConditionTransitions;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Tests\TestCase;

/**
 * @covers \Tochka\Promises\Core\Support\ConditionTransitions
 */
class ConditionTransitionsTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Core\Support\ConditionTransitions::setConditions
     * @covers \Tochka\Promises\Core\Support\ConditionTransitions::addCondition
     * @covers \Tochka\Promises\Core\Support\ConditionTransitions::getConditions
     */
    public function testSetConditions(): void
    {
        $condition1 = new ConditionTransition(new Positive(), StateEnum::WAITING(), StateEnum::RUNNING());
        $condition2 = new ConditionTransition(new Positive(), StateEnum::RUNNING(), StateEnum::SUCCESS());
        $condition3 = new ConditionTransition(new Positive(), StateEnum::SUCCESS(), StateEnum::CANCELED());

        $mock = \Mockery::mock(ConditionTransitions::class);
        $mock->setConditions(
            [
                $condition1,
                $condition2,
            ]
        );
        $mock->addCondition($condition3);

        $result = $mock->getConditions();

        self::assertEquals(
            [
                $condition1,
                $condition2,
                $condition3,
            ],
            $result
        );
    }
}
