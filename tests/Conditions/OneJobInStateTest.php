<?php

namespace Tochka\Promises\Tests\Conditions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tochka\Promises\Conditions\OneJobInState;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseJob;
use Tochka\Promises\Tests\TestCase;

/**
 * @covers \Tochka\Promises\Conditions\OneJobInState
 */
class OneJobInStateTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        Promise::factory()
            ->has(PromiseJob::factory(['state' => StateEnum::SUCCESS()]), 'jobs')
            ->has(PromiseJob::factory(['state' => StateEnum::FAILED()]), 'jobs')
            ->has(PromiseJob::factory(['state' => StateEnum::SUCCESS()]), 'jobs')
            ->create(['id' => 1]);

        Promise::factory()
            ->has(PromiseJob::factory(['state' => StateEnum::SUCCESS()]), 'jobs')
            ->has(PromiseJob::factory(['state' => StateEnum::SUCCESS()]), 'jobs')
            ->has(PromiseJob::factory(['state' => StateEnum::SUCCESS()]), 'jobs')
            ->create(['id' => 2]);
    }

    public function conditionProvider(): array
    {
        return [
            'OneOf True'  => [[StateEnum::TIMEOUT(), StateEnum::FAILED()], 1, true],
            'OneOf False' => [[StateEnum::TIMEOUT(), StateEnum::FAILED()], 2, false],
            'One True'    => [[StateEnum::FAILED()], 1, true],
            'One False'   => [[StateEnum::FAILED()], 2, false],
        ];
    }

    /**
     * @dataProvider conditionProvider
     * @covers       \Tochka\Promises\Conditions\OneJobInState::condition
     * @covers       \Tochka\Promises\Conditions\OneJobInState::getStates
     *
     * @param array<StateEnum> $states
     * @param int              $promiseId
     * @param bool             $expected
     */
    public function testCondition(array $states, int $promiseId, bool $expected): void
    {
        /** @var Promise $promise */
        $promise = Promise::with('jobs')->find($promiseId);
        $basePromise = $promise->getBasePromise();

        $condition = new OneJobInState($states);
        $result = $condition->condition($basePromise);

        self::assertEqualsCanonicalizing($states, $condition->getStates());
        self::assertEquals($expected, $result);
    }
}
