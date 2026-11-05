<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class SanctumSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_api_user_route_requires_a_token(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_user_can_issue_a_hashed_token_and_access_the_default_api_route(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('uji-sanctum');

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertNotSame($token->plainTextToken, $token->accessToken->token);
        $this->assertSame($user->id, PersonalAccessToken::findToken($token->plainTextToken)->tokenable_id);

        $this->withToken($token->plainTextToken)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonMissingPath('password');
    }
}
