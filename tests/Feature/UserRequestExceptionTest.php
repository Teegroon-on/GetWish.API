<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserRequestExceptionTest extends TestCase
{
    use WithFaker, RefreshDatabase;

    /**
     * Get user data without authorization
     *
     * @return void
     */
    public function test_get_without_auth()
    {
        $response = $this->getJson('/api/v1/user');

        $response->assertStatus(401);
    }

    /**
     * Update user data without authorization
     *
     * @return void
     */
    public function test_update_without_auth()
    {
        $response = $this->putJson('/api/v1/user/1/');

        $response->assertStatus(401);
    }

    /**
     * Try update another user
     *
     * @return void
     */
    public function test_update_another_user()
    {
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );

        $newUser = User::factory()->create();

        $response = $this->putJson('/api/v1/user/'.$newUser->id.'/');

        $response->assertStatus(403);
    }

    /**
     * Update with existing username
     *
     * @return void
     */
    public function test_update_with_existing_username()
    {
        $user = User::factory()->create();

        Sanctum::actingAs(
            $user,
            ['*']
        );

        $newUser = User::factory()->create();

        $response = $this->putJson('/api/v1/user/'.$user->id.'/', [
            'username' => $newUser->username,
        ]);

        $response->assertStatus(461);
    }

    /**
     * Update user data without authorization
     *
     * @return void
     */
    public function test_check_username_availability_without_auth()
    {
        $response = $this->postJson('/api/v1/user/availability');

        $response->assertStatus(401);
    }

    /**
     * Check username availability
     *
     * @return void
     */
    public function test_check_username_availability_with_existing_username()
    {
        $user = User::factory()->create();

        Sanctum::actingAs(
            $user,
            ['*']
        );

        $newUser = User::factory()->create();

        $response = $this->postJson('/api/v1/user/availability', [
            'username' => $newUser->username,
        ]);

        $response->assertStatus(461);
    }
}
