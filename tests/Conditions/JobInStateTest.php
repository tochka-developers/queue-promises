<?php

namespace Tochka\Promises\Tests\Conditions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tochka\Promises\Conditions\JobInState;
use Tochka\Promises\Core\BaseJob;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseJob;
use Tochka\Promises\Tests\TestCase;
use Tochka\Promises\Tests\TestHelpers\TestPromise;

/**
 * @covers \Tochka\Promises\Conditions\JobInState
 */
class JobInStateTest extends TestCase
{
    use RefreshDatabase;

    private BasePromise $basePromise;
    private BaseJob $baseJob;

    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        /** @var Promise $promise */
        $promise = Promise::factory()
            ->has(PromiseJob::factory(['state' => StateEnum::SUCCESS(), 'id' => 1]), 'jobs')
            ->create(['id' => 1]);

        $this->basePromise = $promise->getBasePromise();

        $job = PromiseJob::find(1);
        $this->baseJob = $job->getBaseJob();
    }

    public static function conditionProvider(): array
    {
        return [
            'OneOf True'  => [[StateEnum::SUCCESS(), StateEnum::FAILED()], true],
            'OneOf False' => [[StateEnum::WAITING(), StateEnum::FAILED()], false],
            'One True'    => [[StateEnum::SUCCESS()], true],
            'One False'   => [[StateEnum::WAITING()], false],
        ];
    }

    /**
     * @dataProvider conditionProvider
     * @covers       \Tochka\Promises\Conditions\JobInState::condition
     * @covers       \Tochka\Promises\Conditions\JobInState::getStates
     *
     * @param array<StateEnum> $states
     * @param bool             $expected
     */
    public function testCondition(array $states, bool $expected): void
    {
        $condition = new JobInState($this->baseJob, $states);
        $result = $condition->condition($this->basePromise);

        self::assertEqualsCanonicalizing($states, $condition->getStates());
        self::assertEquals($expected, $result);
    }

    /**
     * @covers \Tochka\Promises\Conditions\JobInState::condition
     */
    public function testEmptyJobCondition(): void
    {
        $basePromise = new BasePromise(new TestPromise());

        $condition = new JobInState($this->baseJob, [StateEnum::SUCCESS()]);
        $result = $condition->condition($basePromise);

        self::assertTrue($result);
    }
}
