<?php

namespace Tochka\Promises\Tests\Support;

use Tochka\Promises\Conditions\AllJobsInStates;
use Tochka\Promises\Conditions\JobInState;
use Tochka\Promises\Conditions\OneJobInState;
use Tochka\Promises\Conditions\Positive;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Support\Sync;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestJob;
use Tochka\Promises\Tests\TestHelpers\TestPromise;

/**
 * @covers \Tochka\Promises\Support\Sync
 */
class SyncTest extends TestCase
{

    /**
     * @covers \Tochka\Promises\Support\Sync::promiseConditionsSync
     */
    public function testPromiseConditionsSync(): void
    {
        $basePromise = new BasePromise(new TestPromise());
        $mock = \Mockery::mock(Sync::class);
        $mock->promiseConditionsSync($basePromise);

        $conditions = $basePromise->getConditions();

        self::assertCount(2, $conditions);

        foreach ($conditions as $condition) {
            if ($condition->getCondition() instanceof AllJobsInStates) {
                self::assertEquals(StateEnum::RUNNING(), $condition->getFromState());
                self::assertEquals(StateEnum::SUCCESS(), $condition->getToState());
            } elseif ($condition->getCondition() instanceof OneJobInState) {
                self::assertEquals(StateEnum::RUNNING(), $condition->getFromState());
                self::assertEquals(StateEnum::FAILED(), $condition->getToState());
            } else {
                self::fail('Unknown condition');
            }
        }
    }

    /**
     * @covers \Tochka\Promises\Support\Sync::jobConditionsSync
     * @covers \Tochka\Promises\Support\Sync::afterRunSync
     */
    public function testJobConditionsSync(): void
    {
        $basePromise = new BasePromise(new TestPromise());
        $baseJob1 = new BaseJob(1, new TestJob('test'));
        $baseJob2 = new BaseJob(1, new TestJob('test'));
        $baseJob3 = new BaseJob(1, new TestJob('test'));

        $mock = \Mockery::mock(Sync::class);
        $mock->jobConditionsSync($basePromise, $baseJob1);
        $mock->jobConditionsSync($basePromise, $baseJob2);

        $mock->afterRunSync();
        $mock->jobConditionsSync($basePromise, $baseJob3);

        $conditions = $baseJob1->getConditions();
        self::assertCount(1, $conditions);

        foreach ($conditions as $condition) {
            if ($condition->getCondition() instanceof Positive) {
                self::assertEquals(StateEnum::WAITING(), $condition->getFromState());
                self::assertEquals(StateEnum::RUNNING(), $condition->getToState());
            } else {
                self::fail('Unknown condition');
            }
        }

        $conditions = $baseJob2->getConditions();
        self::assertCount(1, $conditions);

        foreach ($conditions as $condition) {
            if ($condition->getCondition() instanceof JobInState) {
                self::assertEquals(StateEnum::WAITING(), $condition->getFromState());
                self::assertEquals(StateEnum::RUNNING(), $condition->getToState());
            } else {
                self::fail('Unknown condition');
            }
        }

        $conditions = $baseJob3->getConditions();
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
