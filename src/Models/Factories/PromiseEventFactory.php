<?php
/** @noinspection PhpMissingFieldTypeInspection */

namespace Tochka\Promises\Models\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tochka\Promises\Models\PromiseEvent;

class PromiseEventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PromiseEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'job_id'          => $this->faker->randomNumber(5),
            'event_name'      => 'MyEvent',
            'event_unique_id' => $this->faker->randomNumber(5),
        ];
    }
}
