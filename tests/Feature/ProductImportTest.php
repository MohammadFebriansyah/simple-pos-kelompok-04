<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_skips_invalid_rows_and_can_be_repeated_without_duplicates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Minuman']);
        $csv = "name,category_id,price\nKopi Hitam,{$categoryId},15000\nTeh Manis,{$categoryId},10000\nTanpa Kategori,999,5000\nHarga Salah,{$categoryId},-1\nKolom Kurang,{$categoryId}\n";

        $this->actingAs($admin)->get('/import')->assertOk()->assertSee('Import Produk dari CSV');
        $this->from('/import')->post('/import', [
            'csv' => UploadedFile::fake()->createWithContent('produk.csv', $csv),
        ])->assertRedirect('/import')->assertSessionHas('success', '2 produk baru, 0 diperbarui.');

        $this->assertDatabaseCount('products', 2);
        Product::where('name', 'Kopi Hitam')->update(['price' => 20000, 'stock' => 7]);

        $this->from('/import')->post('/import', [
            'csv' => UploadedFile::fake()->createWithContent('produk.csv', $csv),
        ])->assertRedirect('/import')->assertSessionHas('success', '0 produk baru, 2 diperbarui.');

        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseHas('products', ['name' => 'Kopi Hitam', 'price' => 15000, 'stock' => 7]);
    }

    public function test_import_requires_a_file_with_the_expected_header(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post('/import')->assertSessionHasErrors('csv');

        foreach (['', "name;category_id;price\nKopi;1;15000"] as $csv) {
            $this->post('/import', [
                'csv' => UploadedFile::fake()->createWithContent('produk.csv', $csv),
            ])->assertSessionHasErrors('csv');
        }

        $this->assertDatabaseCount('products', 0);
    }

    public function test_import_is_only_accessible_to_admins(): void
    {
        $this->get('/import')->assertRedirect('/login');
        $this->post('/import')->assertRedirect('/login');

        foreach (['kasir', 'manager'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->get('/import')->assertForbidden();
            $this->post('/import')->assertForbidden();
        }
    }
}
