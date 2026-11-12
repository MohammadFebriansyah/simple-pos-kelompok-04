<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ApiLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_credentials_issue_a_token_for_the_user(): void
    {
        $user = User::factory()->create(['email' => 'kasir@pos.test', 'password' => 'password']);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'kasir@pos.test',
            'password' => 'password',
        ])->assertOk()->assertJsonStructure(['token']);

        $token = PersonalAccessToken::findToken($response->json('token'));
        $this->assertNotNull($token);
        $this->assertSame($user->id, $token->tokenable_id);
        $this->assertSame('mobile-kasir', $token->name);
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $response->assertJsonMissingPath('password');
    }

    public function test_incorrect_credentials_do_not_issue_a_token(): void
    {
        User::factory()->create(['email' => 'kasir@pos.test', 'password' => 'password']);

        foreach (['kasir@pos.test', 'tidak-ada@pos.test'] as $email) {
            $this->postJson('/api/v1/login', ['email' => $email, 'password' => 'salah'])
                ->assertUnauthorized()
                ->assertExactJson(['message' => 'Kredensial tidak valid.']);
        }

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_rejects_missing_or_invalid_input(): void
    {
        $this->postJson('/api/v1/login')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);

        $this->postJson('/api/v1/login', ['email' => 'bukan-email', 'password' => 'password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
