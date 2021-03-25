<?php

namespace Tochka\Promises\Tests\Support;

use Tochka\Promises\Conditions\AllJobsInStates;
use Tochka\Promises\Conditions\AndConditions;
use Tochka\Promises\Conditions\Positive;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Support\ASync;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestJob;
use Tochka\Promises\Tests\TestHelpers\TestPromise;

/**
 * @covers \Tochka\Promises\Support\ASync
 */
class ASyncTest extends TestCase
{

    /**
     * @covers \Tochka\Promises\Support\ASync::promiseConditionsASync
     */
    public function testPromiseConditionsASync(): void
    {
        $basePromise = new BasePromise(new TestPromise());
        $mock = \Mockery::mock(ASync::class);
        $mock->promiseConditionsASync($basePromise);

        $conditions = $basePromise->getConditions();
        self::assertCount(2, $conditions);

        foreach ($conditions as $condition) {
            if ($condition->getCondition() instanceof AllJobsInStates) {
                self::assertEquals(StateEnum::RUNNING(), $condition->getFromState());
                self::assertEquals(StateEnum::SUCCESS(), $condition->getToState());
            } elseif ($condition->getCondition() instanceof AndConditions) {
                self::assertEquals(StateEnum::RUNNING(), $condition->getFromState());
                self::assertEquals(StateEnum::FAILED(), $condition->getToState());
            } else {
                self::fail('Unknown condition');
            }
        }
    }

    /**
     * @covers \Tochka\Promises\Support\ASync::jobConditionsASync
     */
    public function testJobConditionsASync(): void
    {
        $basePromise = new BasePromise(new TestPromise());
        $baseJob = new BaseJob(1, new TestJob('test'));

        $mock = \Mockery::mock(ASync::class);
        $mock->jobConditionsASync($basePromise, $baseJob);

        $conditions = $baseJob->getConditions();
        self::assertCount(1, $conditions);

        foreach ($conditions as $condition) {
            if ($condition->getCondition() instanceof Positive) {
                self::assertEquals(StateEnum::WAITING(), $condition->getFromState());
                self::assertEquals(StateEnum::RUNNING(), $condition->getToState());
            } else {
                self::fail('Unknown condition');
            }
        }
    }
}
