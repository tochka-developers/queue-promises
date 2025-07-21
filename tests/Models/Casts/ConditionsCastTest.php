<?php

namespace Tochka\Promises\Tests\Models\Casts;

use Illuminate\Database\Eloquent\Model;
use Tochka\Promises\Conditions\Positive;
use Tochka\Promises\Core\Support\ConditionTransition;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\Casts\ConditionsCast;
use Tochka\Promises\Tests\TestCase;

/**
 * @covers \Tochka\Promises\Models\Casts\ConditionsCast
 */
class ConditionsCastTest extends TestCase
{
    public static function getSetProvider(): array
    {
        return [
            'Full'  => [
                [
                    new ConditionTransition(new Positive(), StateEnum::WAITING(), StateEnum::RUNNING()),
                    new ConditionTransition(new Positive(), StateEnum::RUNNING(), StateEnum::SUCCESS()),
                ],
            ],
            'Empty' => [
                [],
            ],
        ];
    }

    /**
     * @dataProvider getSetProvider
     * @covers       \Tochka\Promises\Models\Casts\ConditionsCast::get
     * @covers       \Tochka\Promises\Models\Casts\ConditionsCast::set
     *
     * @param array<\Tochka\Promises\Contracts\ConditionTransitionsContract> $conditions
     *
     * @throws \JsonException
     */
    public function testGetSet(array $conditions): void
    {
        $model = \Mockery::mock(Model::class);

        $cast = new ConditionsCast();
        $casted = $cast->set($model, 'test', $conditions, []);
        $result = $cast->get($model, 'test', $casted['test'], $casted);

        self::assertEquals($conditions, $result);
    }
}
