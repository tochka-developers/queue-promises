<?php

namespace Tochka\Promises\Models\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\Promise;
use Tochka\Promises\Tests\TestHelpers\TestPromise;

/**
 * @codeCoverageIgnore
 * @template-extends Factory<Promise>
 */
class PromiseFactory extends Factory
{
    protected $model = Promise::class;

    public function definition(): array
    {
        return [
            'state' => StateEnum::WAITING(),
            'conditions' => [],
            'promise_handler' => new TestPromise(),
            'watch_at' => Carbon::now(),
            'timeout_at' => Carbon::now()->addWeek(),
        ];
    }
}
