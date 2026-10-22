<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoUserSeeder::class);
    }

    public function test_manager_can_access_transactions(): void
    {
        $manager = User::where('role', 'manager')->first();

        $response = $this->actingAs($manager)->get('/transactions');

        $response->assertStatus(200);
    }

    public function test_manager_cannot_access_products(): void
    {
        $manager = User::where('role', 'manager')->first();

        $response = $this->actingAs($manager)->get('/products');

        $response->assertStatus(403);
    }

    public function test_kasir_cannot_access_transactions(): void
    {
        $kasir = User::where('role', 'kasir')->first();

        $response = $this->actingAs($kasir)->get('/transactions');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_both_transactions_and_products(): void
    {
        $admin = User::where('role', 'admin')->first();

        $this->actingAs($admin)->get('/transactions')->assertStatus(200);
        $this->actingAs($admin)->get('/products')->assertStatus(200);
    }
}
