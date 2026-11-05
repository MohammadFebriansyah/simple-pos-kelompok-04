<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_or_invalid_token_is_rejected(): void
    {
        $this->getJson('/api/products')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');

        $this->withToken('token-tidak-valid')->getJson('/api/products')->assertUnauthorized();
        $this->postJson('/api/logout')->assertUnauthorized();
    }

    public function test_bearer_token_returns_only_the_product_resource_fields(): void
    {
        $user = User::factory()->create();
        $category = Category::forceCreate(['name' => 'Minuman']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kopi',
            'price' => 15000,
            'stock' => 10,
        ]);
        $token = $user->createToken('uji-produk')->plainTextToken;

        $this->withToken($token)->getJson('/api/products')
            ->assertOk()
            ->assertExactJson(['data' => [[
                'id' => $product->id,
                'name' => 'Kopi',
                'price' => 15000,
                'category' => 'Minuman',
            ]]]);
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('uji-logout');
        $otherToken = $user->createToken('perangkat-lain');

        $this->withToken($token->plainTextToken)->postJson('/api/logout')
            ->assertOk()
            ->assertExactJson(['message' => 'Logout berhasil.']);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherToken->accessToken->id]);

        // Bersihkan guard antar-request agar token diperiksa lagi dari database.
        $this->app['auth']->forgetGuards();
        $this->withToken($token->plainTextToken)->getJson('/api/products')->assertUnauthorized();

        $this->app['auth']->forgetGuards();
        $this->withToken($otherToken->plainTextToken)->getJson('/api/products')
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }
}
