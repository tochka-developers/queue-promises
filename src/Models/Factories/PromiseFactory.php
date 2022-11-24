<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace Tochka\Promises\Models\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Tests\TestHelpers\TestPromise;

/**
 * @codeCoverageIgnore
 */
class PromiseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Promise::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'state'           => StateEnum::WAITING(),
            'conditions'      => [],
            'promise_handler' => new TestPromise(),
            'watch_at'        => Carbon::now(),
            'timeout_at'      => Carbon::now()->addWeek(),
        ];
    }
}
