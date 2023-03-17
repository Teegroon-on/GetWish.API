<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthRequestValidationTest extends TestCase
{
    use WithFaker;

    /**
     * Check code/send validation.
     *
     * @return void
     */
    public function test_request_code_send()
    {
        $response = $this->postJson('/api/v1/auth/code/send', [
            'phone' => $this->faker->numerify('+79#######'),
        ]);

        $response->assertStatus(400);

        $response = $this->postJson('/api/v1/auth/code/send', [
            'phone' => $this->faker->numerify('8800#######'),
        ]);

        $response->assertStatus(400);

        $response = $this->postJson('/api/v1/auth/code/send', [
            'phone' => $this->faker->numerify('+7##########'),
        ]);

        $response->assertStatus(200);
    }

    /**
     * Check code/check validation.
     *
     * @return void
     */
    public function test_request_code_check()
    {
        $response = $this->postJson('/api/v1/auth/code/check', [
            'phone' => $this->faker->numerify('+79#######'),
            'code' => $this->faker->numerify('####'),
        ]);

        $response->assertStatus(400);

        $response = $this->postJson('/api/v1/auth/code/check', [
            'phone' => $this->faker->numerify('8800#######'),
            'code' => $this->faker->numerify('####'),
        ]);

        $response->assertStatus(400);

        $response = $this->postJson('/api/v1/auth/code/check', [
            'phone' => $this->faker->numerify('+7##########'),
            'code' => $this->faker->numerify('#####'),
        ]);

        $response->assertStatus(400);
    }

    /**
     * Check token/refresh validation.
     *
     * @return void
     */
    public function test_request_token_refresh()
    {
        $response = $this->postJson('/api/v1/auth/token/refresh', [
            'refresh_token' => $this->faker->regexify('/[a-z0-9]{40}/'),
        ]);

        $response->assertStatus(400);

        $response = $this->postJson('/api/v1/auth/token/refresh', [
            'refresh_token' => null,
        ]);

        $response->assertStatus(400);
    }
}
