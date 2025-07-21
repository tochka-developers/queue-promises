<?php

namespace Tochka\Promises\Tests\Conditions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tochka\Promises\Conditions\EmptyJobs;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Models\PromiseJob;
use Tochka\Promises\Tests\TestCase;

/**
 * @covers \Tochka\Promises\Conditions\EmptyJobs
 */
class EmptyJobsTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        Promise::factory()
            ->has(PromiseJob::factory(['state' => StateEnum::WAITING()]), 'jobs')
            ->has(PromiseJob::factory(['state' => StateEnum::RUNNING()]), 'jobs')
            ->create(['id' => 1]);

        Promise::factory()
            ->create(['id' => 2]);
    }

    public static function conditionProvider(): array
    {
        return [
            'False' => [1, false],
            'True'  => [2, true],
        ];
    }

    /**
     * @dataProvider conditionProvider
     * @covers       \Tochka\Promises\Conditions\EmptyJobs::condition
     *
     * @param int  $promiseId
     * @param bool $expected
     */
    public function testCondition(int $promiseId, bool $expected): void
    {
        $promise = Promise::find($promiseId);
        $condition = new EmptyJobs();
        $result = $condition->condition($promise->getBasePromise());

        self::assertEquals($expected, $result);
    }
}
