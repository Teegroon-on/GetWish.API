<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserRequestValidationTest extends TestCase
{
    use WithFaker, RefreshDatabase;

    /**
     * Check user/availability.
     *
     * @return void
     */
    public function test_request_user_availability()
    {
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );

        $response = $this->postJson('/api/v1/user/availability', [
            'username' => $this->faker->regexify('/[a-z0-9]{2}/'),
        ]);

        $response->assertStatus(400);

        $response = $this->postJson('/api/v1/user/availability', [
            'username' => $this->faker->regexify('/[a-z0-9]{31}/'),
        ]);

        $response->assertStatus(400);

        $response = $this->postJson('/api/v1/user/availability', [
            'username' => $this->faker->regexify('/[\%\?\(\)]{10}/'),
        ]);

        $response->assertStatus(400);
    }

    /**
     * Check user/update.
     *
     * @return void
     */
    public function test_request_user_update()
    {
        $user = User::factory()->create();

        Sanctum::actingAs(
            $user,
            ['*']
        );

        $response = $this->putJson('/api/v1/user/'.$user->id.'/', [
            'username' => $this->faker->regexify('/[a-z0-9]{2}/'),
        ]);

        $response->assertStatus(400);

        $response = $this->putJson('/api/v1/user/'.$user->id.'/', [
            'username' => $this->faker->regexify('/[a-z0-9]{31}/'),
        ]);

        $response->assertStatus(400);

        $response = $this->putJson('/api/v1/user/'.$user->id.'/', [
            'birthdate' => '0000-00-00',
        ]);

        $response->assertStatus(400);

        $response = $this->putJson('/api/v1/user/'.$user->id.'/', [
            'birthdate' => '1900-13-01',
        ]);

        $response->assertStatus(400);
        $response = $this->putJson('/api/v1/user/'.$user->id.'/', [
            'birthdate' => '1900-01-32',
        ]);

        $response->assertStatus(400);
    }

    /**
     * Check user/sendCode validation.
     *
     * @return void
     */
    public function test_request_user_send_code()
    {
        $user = User::factory()->create();

        Sanctum::actingAs(
            $user,
            ['*']
        );

        $response = $this->postJson('/api/v1/user/sendCode', [
            'phone' => $this->faker->numerify('+79#######'),
        ]);

        $response->assertStatus(400);

        $response = $this->postJson('/api/v1/user/sendCode', [
            'phone' => $this->faker->numerify('8800#######'),
        ]);

        $response->assertStatus(400);
    }

    /**
     * Check user/updatePhone validation.
     *
     * @return void
     */
    public function test_request_user_update_phone()
    {
        $user = User::factory()->create();

        Sanctum::actingAs(
            $user,
            ['*']
        );

        $response = $this->postJson('/api/v1/user/updatePhone', [
            'phone' => $this->faker->numerify('+79#######'),
            'code' => $this->faker->numerify('####'),
        ]);

        $response->assertStatus(400);

        $response = $this->postJson('/api/v1/user/updatePhone', [
            'phone' => $this->faker->numerify('8800#######'),
            'code' => $this->faker->numerify('####'),
        ]);

        $response->assertStatus(400);

        $response = $this->postJson('/api/v1/user/updatePhone', [
            'phone' => $this->faker->numerify('+7##########'),
            'code' => $this->faker->numerify('#####'),
        ]);

        $response->assertStatus(400);
    }
}
