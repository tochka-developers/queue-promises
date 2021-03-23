<?php

namespace Tochka\Promises\Tests\Core\Support;

use Tochka\Promises\Core\Support\States;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Tests\TestCase;

/**
 * @covers \Tochka\Promises\Core\Support\States
 */
class StatesTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Core\Support\States::getState
     * @covers \Tochka\Promises\Core\Support\States::setState
     */
    public function testGetSetState(): void
    {
        $mock = \Mockery::mock(States::class);
        $mock->setState(StateEnum::SUCCESS());
        $result = $mock->getState();

        self::assertEquals(StateEnum::SUCCESS(), $result);
    }
}
