<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'username' => $this->faker->unique()->userName(),
            'phone' => $this->faker->unique()->numerify('+79#########'),
            'refresh_token' => $this->faker->unique()->regexify('[a-z0-9]{64}'),
        ];
    }
}
