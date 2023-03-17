<?php

namespace Tests\Feature;

use App\Models\SmsCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthRequestExceptionTest extends TestCase
{
    use WithFaker, RefreshDatabase;

    /**
     * Send code on phone with still active code
     *
     * @return void
     */
    public function test_send_code_with_active_sms_code()
    {
        $smsCode = SmsCode::factory()->create();

        $response = $this->postJson('/api/v1/auth/code/send', [
            'phone' => $smsCode->phone,
        ]);

        $response->assertStatus(460);

        $smsCode->created_at = now()->subSeconds(61);
        $smsCode->save();

        $response = $this->postJson('/api/v1/auth/code/send', [
            'phone' => $smsCode->phone,
        ]);

        $response->assertStatus(200);
    }

    /**
     * Check not exists code
     *
     * @return void
     */
    public function test_check_not_exists_code()
    {
        $fakeCode = $this->faker->unique()->numerify('####');
        $smsCode = SmsCode::factory()->create([
            'code' => $fakeCode,
        ]);

        $response = $this->postJson('/api/v1/auth/code/check', [
            'phone' => $smsCode->phone,
            'code' => $this->faker->unique()->numerify('####'),
        ]);

        $response->assertStatus(462);
    }

    /**
     * Check expired code
     *
     * @return void
     */
    public function test_check_expired_code()
    {
        $fakeCode = $this->faker->unique()->numerify('####');
        $smsCode = SmsCode::factory()->create([
            'code' => $fakeCode,
            'created_at' => now()->subSeconds(61),
        ]);

        $response = $this->postJson('/api/v1/auth/code/check', [
            'phone' => $smsCode->phone,
            'code' => $smsCode->code,
        ]);

        $response->assertStatus(461);
    }

    /**
     * Check not exists refresh token
     *
     * @return void
     */
    public function test_not_exist_refresh_token()
    {
        $fakeRefreshToken = $this->faker->unique()->regexify('[a-z0-9]{64}');
        $user = User::factory()->create([
            'refresh_token' => $fakeRefreshToken,
            'refresh_token_expired_at' => now()->addMinute(),
        ]);

        $response = $this->postJson('/api/v1/auth/token/refresh', [
            'refresh_token' => $this->faker->unique()->regexify('[a-z0-9]{64}'),
        ]);

        $response->assertStatus(401);
    }

    /**
     * Check expired refresh token
     *
     * @return void
     */
    public function test_expired_refresh_token()
    {
        $user = User::factory()->create([
            'refresh_token_expired_at' => now()->subSecond(),
        ]);

        $response = $this->postJson('/api/v1/auth/token/refresh', [
            'refresh_token' => $user->refresh_token,
        ]);

        $response->assertStatus(401);
    }
}
