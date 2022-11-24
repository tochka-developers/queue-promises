<?php

namespace Tochka\Promises\Tests\Core;

use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\PromiseJob;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestJob;

/**
 * @covers \Tochka\Promises\Core\BaseJob
 */
class BaseJobTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Core\BaseJob::getAttachedModel
     * @covers \Tochka\Promises\Core\BaseJob::setAttachedModel
     */
    public function testGetSetAttachedModel(): void
    {
        $expected = new PromiseJob();

        $mock = \Mockery::mock(BaseJob::class);
        $mock->makePartial();

        $mock->setAttachedModel($expected);
        $result = $mock->getAttachedModel();

        self::assertEquals($expected, $result);
    }

    /**
     * @covers \Tochka\Promises\Core\BaseJob::getJobId
     * @covers \Tochka\Promises\Core\BaseJob::setJobId
     */
    public function testGetSetJobId(): void
    {
        $expected = 123;

        $mock = \Mockery::mock(BaseJob::class);
        $mock->makePartial();

        self::assertNull($mock->getJobId());

        $mock->setJobId($expected);
        $result = $mock->getJobId();

        self::assertEquals($expected, $result);
    }

    /**
     * @covers \Tochka\Promises\Core\BaseJob::getInitialJob
     * @covers \Tochka\Promises\Core\BaseJob::setInitial
     */
    public function testGetSetInitialJob(): void
    {
        $expected = new TestJob('test');

        $mock = \Mockery::mock(BaseJob::class);
        $mock->makePartial();

        $mock->setInitial($expected);
        $result = $mock->getInitialJob();

        self::assertEquals($expected, $result);
    }

    /**
     * @covers \Tochka\Promises\Core\BaseJob::getResultJob
     * @covers \Tochka\Promises\Core\BaseJob::setResult
     */
    public function testGetSetResultJob(): void
    {
        $expected = new TestJob('test');

        $mock = \Mockery::mock(BaseJob::class);
        $mock->makePartial();

        $mock->setResult($expected);
        $result = $mock->getResultJob();

        self::assertEquals($expected, $result);
    }

    /**
     * @covers \Tochka\Promises\Core\BaseJob::__construct
     * @covers \Tochka\Promises\Core\BaseJob::getPromiseId
     */
    public function testConstruct(): void
    {
        $expectedPromiseId = 123;
        $expectedInitialJob = new TestJob('initial');
        $expectedResultJob = new TestJob('result');

        $baseJob = new BaseJob($expectedPromiseId, $expectedInitialJob, $expectedResultJob);

        self::assertEquals($expectedPromiseId, $baseJob->getPromiseId());
        self::assertEquals($expectedInitialJob, $baseJob->getInitialJob());
        self::assertEquals($expectedResultJob, $baseJob->getResultJob());
        self::assertEquals(StateEnum::WAITING(), $baseJob->getState());
    }

    /**
     * @covers \Tochka\Promises\Core\BaseJob::__construct
     * @covers \Tochka\Promises\Core\BaseJob::getPromiseId
     */
    public function testConstructWithoutResult(): void
    {
        $expectedPromiseId = 123;
        $expectedInitialJob = new TestJob('initial');

        $baseJob = new BaseJob($expectedPromiseId, $expectedInitialJob);

        self::assertEquals($expectedPromiseId, $baseJob->getPromiseId());
        self::assertEquals($expectedInitialJob, $baseJob->getInitialJob());
        self::assertEquals($expectedInitialJob, $baseJob->getResultJob());
    }

    /**
     * @covers \Tochka\Promises\Core\BaseJob::getException
     * @covers \Tochka\Promises\Core\BaseJob::setException
     */
    public function testGetSetException(): void
    {
        $expected = new \RuntimeException('test');

        $mock = \Mockery::mock(BaseJob::class);
        $mock->makePartial();

        self::assertNull($mock->getException());

        $mock->setException($expected);
        $result = $mock->getException();

        self::assertEquals($expected, $result);
    }
}
