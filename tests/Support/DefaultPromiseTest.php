<?php

namespace Tochka\Promises\Tests\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tochka\Promises\Conditions\EmptyJobs;
use Tochka\Promises\Conditions\PromiseInState;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Facades\Promises;
use Tochka\Promises\Models\PromiseJob;
use Tochka\Promises\Support\DefaultPromise;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestJob;
use Tochka\Promises\Tests\TestHelpers\TestPromise;

/**
 * @covers \Tochka\Promises\Support\DefaultPromise
 */
class DefaultPromiseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @covers \Tochka\Promises\Support\DefaultPromise::getPromiseId
     * @covers \Tochka\Promises\Support\DefaultPromise::setPromiseId
     */
    public function testSetGetPromiseId(): void
    {
        $expected = 123;

        $mock = \Mockery::mock(DefaultPromise::class);

        self::assertNull($mock->getPromiseId());

        $mock->setPromiseId($expected);
        $result = $mock->getPromiseId();

        self::assertEquals($expected, $result);
    }

    /**
     * @covers \Tochka\Promises\Support\DefaultPromise::add
     * @covers \Tochka\Promises\Support\DefaultPromise::run
     */
    public function testAddAndRun(): void
    {
        $expected = [
            new TestJob('test1'),
            new TestJob('test2'),
        ];

        $promise = new TestPromise();

        foreach ($expected as $job) {
            $promise->add($job);
        }

        Promises::shouldReceive('run')
            ->once()
            ->with($promise, $expected);

        $promise->run();
    }

    /**
     * @covers \Tochka\Promises\Support\DefaultPromise::getResults
     */
    public function testGetResults(): void
    {
        $expected1 = new BaseJob(1, new TestJob('test1'));
        $expected2 = new BaseJob(1, new TestJob('test2'));

        PromiseJob::saveBaseJob($expected1);
        PromiseJob::saveBaseJob($expected2);

        $mock = \Mockery::mock(DefaultPromise::class);
        $mock->setPromiseId(1);

        $result = $mock->getResults()->toArray();

        self::assertCount(2, $result);

        /** @var BaseJob $result1 */
        /** @var BaseJob $result2 */
        [$result1, $result2] = $result;

        // тест завязан на порядок получаемых задач, поэтмоу в случае нарушения порядка - тест свалится
        self::assertEquals($expected1->getPromiseId(), $result1->getPromiseId());
        self::assertEquals($expected1->getInitialJob(), $result1->getInitialJob());
        self::assertEquals($expected1->getJobId(), $result1->getJobId());

        self::assertEquals($expected2->getPromiseId(), $result2->getPromiseId());
        self::assertEquals($expected2->getInitialJob(), $result2->getInitialJob());
        self::assertEquals($expected2->getJobId(), $result2->getJobId());
    }

    /**
     * @covers \Tochka\Promises\Support\DefaultPromise::promiseConditionsDefaultPromise
     */
    public function testPromiseConditionsDefaultPromise(): void
    {
        $basePromise = new BasePromise(new TestPromise());
        $mock = \Mockery::mock(DefaultPromise::class);
        $mock->promiseConditionsDefaultPromise($basePromise);

        $conditions = $basePromise->getConditions();
        self::assertCount(1, $conditions);

        foreach ($conditions as $condition) {
            if ($condition->getCondition() instanceof EmptyJobs) {
                self::assertEquals(StateEnum::RUNNING(), $condition->getFromState());
                self::assertEquals(StateEnum::SUCCESS(), $condition->getToState());
            } else {
                self::fail('Unknown condition');
            }
        }
    }

    /**
     * @covers \Tochka\Promises\Support\DefaultPromise::jobConditionsDefaultPromise
     */
    public function testJobConditionsDefaultPromise(): void
    {
        $basePromise = new BasePromise(new TestPromise());
        $baseJob = new BaseJob(1, new TestJob('test'));

        $mock = \Mockery::mock(DefaultPromise::class);
        $mock->jobConditionsDefaultPromise($basePromise, $baseJob);

        $conditions = $baseJob->getConditions();
        self::assertCount(2, $conditions);

        foreach ($conditions as $condition) {
            if ($condition->getCondition() instanceof PromiseInState) {
                if ($condition->getFromState()->is(StateEnum::WAITING())) {
                    self::assertEquals(StateEnum::CANCELED(), $condition->getToState());
                } elseif ($condition->getFromState()->is(StateEnum::RUNNING())) {
                    self::assertEquals(StateEnum::TIMEOUT(), $condition->getToState());
                } else {
                    self::fail('Unknown condition');
                }
            } else {
                self::fail('Unknown condition');
            }
        }
    }
}
