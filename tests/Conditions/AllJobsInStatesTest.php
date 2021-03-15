<?php

namespace Tochka\Promises\Tests\Conditions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tochka\Promises\Conditions\AllJobsInStates;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseJob;
use Tochka\Promises\Tests\TestCase;

/**
 * @covers \Tochka\Promises\Conditions\AllJobsInStates
 */
class AllJobsInStatesTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<AllJobsInStates> */
    private array $conditions = [];

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
            'OneOf False' => [[StateEnum::TIMEOUT(), StateEnum::SUCCESS()], 1, false],
            'OneOf True'  => [[StateEnum::TIMEOUT(), StateEnum::SUCCESS()], 2, true],
            'One False'   => [[StateEnum::SUCCESS()], 1, false],
            'One True'    => [[StateEnum::SUCCESS()], 2, true],
        ];
    }

    /**
     * @dataProvider conditionProvider
     * @covers       \Tochka\Promises\Conditions\AllJobsInStates::getStates
     * @covers       \Tochka\Promises\Conditions\AllJobsInStates::condition
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
        $condition = new AllJobsInStates($states);
        $result = $condition->condition($basePromise);

        self::assertEqualsCanonicalizing($states, $condition->getStates());
        self::assertEquals($expected, $result);
    }
}
