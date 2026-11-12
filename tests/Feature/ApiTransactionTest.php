<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function payload(): array
    {
        $product = Product::factory()->create(['price' => 15000]);

        return ['items' => [['product_id' => $product->id, 'qty' => 2]]];
    }

    public function test_guest_cannot_create_transaction(): void
    {
        $this->postJson('/api/v1/transactions', $this->payload())
            ->assertUnauthorized(); 
    }

    public function test_token_without_ability_is_forbidden(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['products:read']);

        $this->postJson('/api/v1/transactions', $this->payload(), ['Idempotency-Key' => 'k1'])
            ->assertForbidden(); 
    }

    public function test_invalid_items_are_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['transactions:create']);

        $this->postJson(
            '/api/v1/transactions',
            ['items' => [['product_id' => 99999, 'qty' => 1]]],
            ['Idempotency-Key' => 'k2']
        )
            ->assertUnprocessable() 
            ->assertJsonValidationErrors('items.0.product_id');
    }

    public function test_transaction_is_created_once_even_when_repeated(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['transactions:create']);

        $payload = $this->payload();
        $headers = ['Idempotency-Key' => 'k3'];

        $first = $this->postJson('/api/v1/transactions', $payload, $headers)
            ->assertCreated() 
            ->assertJsonPath('data.total', 30000);

        $this->postJson('/api/v1/transactions', $payload, $headers)
            ->assertCreated()
            ->assertHeader('Idempotent-Replayed', 'true')
            ->assertJsonPath('data.id', $first->json('data.id'));

        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_cashier_cannot_view_another_cashiers_transaction(): void
    {
        $owner = User::factory()->create();

        $transaction = Transaction::create([
            'user_id' => $owner->id,
            'total' => 1000
        ]);

        Sanctum::actingAs(User::factory()->create(), ['transactions:read']);

        $this->getJson("/api/v1/transactions/{$transaction->id}")
            ->assertForbidden(); 

        Sanctum::actingAs($owner, ['transactions:read']);

        $this->getJson("/api/v1/transactions/{$transaction->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $transaction->id);
    }

    public function test_expired_token_is_rejected_with_401(): void
    {
        $user = User::factory()->create();

        $newToken = $user->createToken('test-expired', ['*']);
        $plainTextToken = $newToken->plainTextToken;

        $expirationMinutes = config('sanctum.expiration') ?? 60;
        Carbon::setTestNow(now()->addMinutes($expirationMinutes + 5));

        $this->withHeader('Authorization', 'Bearer ' . $plainTextToken)
            ->getJson('/api/v1/products')
            ->assertUnauthorized(); 

        Carbon::setTestNow();
    }
}