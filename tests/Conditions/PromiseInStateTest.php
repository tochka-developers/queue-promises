<?php

namespace Tochka\Promises\Tests\Conditions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tochka\Promises\Conditions\PromiseInState;
use Tochka\Promises\Core\BasePromise;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Tests\TestCase;

/**
 * @covers \Tochka\Promises\Conditions\PromiseInState
 */
class PromiseInStateTest extends TestCase
{
    use RefreshDatabase;

    private BasePromise $basePromise;

    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        /** @var Promise $promise */
        $promise = Promise::factory()
            ->create(['id' => 1, 'state' => StateEnum::TIMEOUT()]);

        $this->basePromise = $promise->getBasePromise();
    }

    public static function conditionProvider(): array
    {
        return [
            'OneOf True'  => [[StateEnum::TIMEOUT(), StateEnum::FAILED()], true],
            'OneOf False' => [[StateEnum::SUCCESS(), StateEnum::FAILED()], false],
            'One True'    => [[StateEnum::TIMEOUT()], true],
            'One False'   => [[StateEnum::FAILED()], false],
        ];
    }

    /**
     * @dataProvider conditionProvider
     * @covers       \Tochka\Promises\Conditions\PromiseInState::condition
     * @covers       \Tochka\Promises\Conditions\PromiseInState::getStates
     *
     * @param array $states
     * @param bool  $expected
     */
    public function testCondition(array $states, bool $expected): void
    {
        $condition = new PromiseInState($states);
        $result = $condition->condition($this->basePromise);

        self::assertEqualsCanonicalizing($states, $condition->getStates());
        self::assertEquals($expected, $result);
    }
}
