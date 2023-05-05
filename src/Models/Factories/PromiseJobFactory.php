<?php

namespace Tochka\Promises\Models\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\PromiseJob;
use Tochka\Promises\Tests\TestHelpers\TestJob;

/**
 * @codeCoverageIgnore
 * @template-extends Factory<PromiseJob>
 */
class PromiseJobFactory extends Factory
{
    protected $model = PromiseJob::class;

    public function definition(): array
    {
        return [
            'promise_id' => $this->faker->randomNumber(5),
            'state' => StateEnum::WAITING(),
            'conditions' => [],
            'initial_job' => new TestJob('initial'),
            'result_job' => new TestJob('result'),
        ];
    }
}
