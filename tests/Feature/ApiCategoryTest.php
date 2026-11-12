<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_or_invalid_token_is_rejected(): void
    {
        $this->getJson('/api/v1/categories')->assertUnauthorized();
        $this->withToken('token-tidak-valid')->getJson('/api/v1/categories')->assertUnauthorized();
    }

    public function test_token_without_category_ability_is_forbidden(): void
    {
        $token = User::factory()->create()->createToken('produk', ['products:read']);

        $this->withToken($token->plainTextToken)->getJson('/api/v1/categories')->assertForbidden();
    }

    public function test_login_token_returns_categories_with_only_id_and_name(): void
    {
        User::factory()->create(['email' => 'kasir@pos.test', 'password' => 'password']);
        $minuman = Category::create(['name' => 'Minuman']);
        $makanan = Category::create(['name' => 'Makanan']);

        $login = $this->postJson('/api/v1/login', [
            'email' => 'kasir@pos.test',
            'password' => 'password',
        ])->assertOk();

        // Request berikutnya menggunakan bearer token, tanpa identitas sesi login.
        $this->app['auth']->forgetGuards();
        $this->withToken($login->json('token'))->getJson('/api/v1/categories')
            ->assertOk()
            ->assertExactJson(['data' => [
                ['id' => $makanan->id, 'name' => 'Makanan'],
                ['id' => $minuman->id, 'name' => 'Minuman'],
            ]]);
    }

    public function test_empty_categories_return_an_empty_data_array(): void
    {
        $token = User::factory()->create()->createToken('kategori', ['categories:read']);

        $this->withToken($token->plainTextToken)->getJson('/api/v1/categories')
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }
}
