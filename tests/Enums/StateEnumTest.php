<?php

namespace Tochka\Promises\Tests\Enums;

use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Tests\TestCase;

/**
 * @covers \Tochka\Promises\Enums\StateEnum
 */
class StateEnumTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Enums\StateEnum::finishedStates
     */
    public function testFinishedStates(): void
    {
        $expected = [
            StateEnum::SUCCESS(),
            StateEnum::FAILED(),
            StateEnum::TIMEOUT(),
        ];
        $states = StateEnum::finishedStates();

        self::assertEquals($expected, $states);
    }

    /**
     * @covers \Tochka\Promises\Enums\StateEnum::successStates
     */
    public function testSuccessStates(): void
    {
        $expected = [
            StateEnum::SUCCESS(),
        ];
        $states = StateEnum::successStates();

        self::assertEquals($expected, $states);
    }

    /**
     * @covers \Tochka\Promises\Enums\StateEnum::failedStates
     */
    public function testFailedStates(): void
    {
        $expected = [
            StateEnum::FAILED(),
            StateEnum::TIMEOUT(),
        ];
        $states = StateEnum::failedStates();

        self::assertEquals($expected, $states);
    }
}
