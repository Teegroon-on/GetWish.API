<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SmsCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'phone' => $this->faker->unique()->numerify('+79#########'),
            'code' => $this->faker->unique()->numerify('####'),
        ];
    }
}
