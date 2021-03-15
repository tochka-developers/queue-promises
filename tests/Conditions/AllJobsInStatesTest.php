<?php

namespace Tochka\Promises\Tests\Conditions;

use PHPUnit\Framework\TestCase;
use Tochka\Promises\Conditions\AllJobsInStates;
use Tochka\Promises\Enums\StateEnum;

class AllJobsInStatesTest extends TestCase
{
    public function testFinished(): void
    {
        $expected = [StateEnum::SUCCESS(), StateEnum::TIMEOUT(), StateEnum::FAILED()];

        $condition = AllJobsInStates::finished();
        $actual = $condition->getStates();

        self::assertEqualsCanonicalizing($expected, $actual);
    }

    public function testSuccess(): void
    {
        $expected = [StateEnum::SUCCESS()];

        $condition = AllJobsInStates::success();
        $actual = $condition->getStates();

        self::assertEqualsCanonicalizing($expected, $actual);
    }

    public function testFailed(): void
    {
        $expected = [StateEnum::TIMEOUT(), StateEnum::FAILED()];

        $condition = AllJobsInStates::failed();
        $actual = $condition->getStates();

        self::assertEqualsCanonicalizing($expected, $actual);
    }

    /**
     * @dataProvider providerCondition
     */
    public function testCondition($condition, $jobs, $result): void
    {
        $condition = AllJobsInStates::failed();

    }

    public function providerCondition(): array
    {
        return [
            [AllJobsInStates::failed(), [], false],
        ];
    }

    public function test__construct(): void
    {
        $expected = [StateEnum::RUNNING(), StateEnum::CANCELED()];
        $condition = new AllJobsInStates($expected);
        $actual = $condition->getStates();

        self::assertEqualsCanonicalizing($expected, $actual);
    }
}
