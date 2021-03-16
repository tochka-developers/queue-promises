<?php

namespace Tochka\Promises\Tests\Listeners\Support;

use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Events\PromiseStateChanged;
use Tochka\Promises\Listeners\Support\FilterTransitionsTrait;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestFilterListener;
use Tochka\Promises\Tests\TestHelpers\TestPromise;

/**
 * @covers \Tochka\Promises\Listeners\Support\FilterTransitionsTrait
 */
class FilterTransitionsTraitTest extends TestCase
{
    /**
     * @covers \Tochka\Promises\Listeners\Support\FilterTransitionsTrait::handle
     */
    public function testHandle(): void
    {
        $transitions = [
            'test'  => [
                'from' => [StateEnum::WAITING()],
                'to'   => [StateEnum::RUNNING()],
            ],
            'test2' => [
                'from' => '*',
                'to'   => '*',
            ],
        ];

        /** @var TestFilterListener|\Mockery\Mock $listener */
        $listener = \Mockery::mock(TestFilterListener::class)->makePartial();
        $listener->transitions = $transitions;

        $basePromise = new BasePromise(new TestPromise());
        $event = new PromiseStateChanged($basePromise, StateEnum::WAITING(), StateEnum::RUNNING());

        $listener->shouldReceive('test')
            ->once()
            ->with($event);

        $listener->shouldReceive('test2')
            ->once()
            ->with($event);

        $listener->handle($event);
    }

    /**
     * @covers \Tochka\Promises\Listeners\Support\FilterTransitionsTrait::getTransitions
     */
    public function testGetTransitions(): void
    {
        $transitions = [
            'test'  => [
                'from' => [StateEnum::WAITING()],
                'to'   => [StateEnum::RUNNING()],
            ],
            'test2' => [
                'from' => '*',
                'to'   => '*',
            ],
        ];

        $listener = \Mockery::mock(FilterTransitionsTrait::class);
        $listener->transitions = $transitions;

        $result = $listener->getTransitions();

        self::assertEquals($transitions, $result);
    }

    /**
     * @covers \Tochka\Promises\Listeners\Support\FilterTransitionsTrait::getTransitions
     */
    public function testGetTransitionsEmpty(): void
    {
        $listener = \Mockery::mock(FilterTransitionsTrait::class);

        $result = $listener->getTransitions();

        self::assertEquals([], $result);
    }
}
