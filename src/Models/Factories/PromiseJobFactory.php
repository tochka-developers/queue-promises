<?php
/** @noinspection PhpMissingFieldTypeInspection */

namespace Tochka\Promises\Models\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tochka\Promises\Enums\StateEnum;
use Tochka\Promises\Models\PromiseJob;
use Tochka\Promises\Tests\TestHelpers\TestJob;

class PromiseJobFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PromiseJob::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'promise_id'  => $this->faker->randomNumber(5),
            'state'       => StateEnum::WAITING(),
            'conditions'  => [],
            'initial_job' => new TestJob('initial'),
            'result_job'  => new TestJob('result'),
        ];
    }
}
