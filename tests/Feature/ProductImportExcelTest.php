<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductImportExcelTest extends TestCase
{
    use RefreshDatabase;

    public function test_excel_import_handles_multiple_chunks_invalid_rows_and_repeat_uploads(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Minuman']);
        $csv = "\xEF\xBB\xBFname,category_id,price\n";
        for ($i = 1; $i <= 501; $i++) {
            $csv .= "Produk {$i},{$categoryId},15000\n";
        }
        $csv .= "Kategori Salah,999999,5000\nHarga Salah,{$categoryId},-1\nKolom Kurang,{$categoryId}\n\n";

        $this->from('/import')->post('/import/excel', [
            'csv' => UploadedFile::fake()->createWithContent('produk.csv', $csv),
        ])->assertRedirect('/import')->assertSessionHas('success', '501 produk baru, 0 diperbarui. (Laravel Excel)');

        $this->assertDatabaseCount('products', 501);
        $this->assertDatabaseHas('products', ['name' => 'Produk 501', 'price' => 15000]);
        Product::where('name', 'Produk 501')->update(['price' => 20000, 'stock' => 7]);

        $this->from('/import')->post('/import/excel', [
            'csv' => UploadedFile::fake()->createWithContent('produk.csv', $csv),
        ])->assertRedirect('/import')->assertSessionHas('success', '0 produk baru, 501 diperbarui. (Laravel Excel)');

        $this->assertDatabaseCount('products', 501);
        $this->assertDatabaseHas('products', ['name' => 'Produk 501', 'price' => 15000, 'stock' => 7]);

        $this->from('/import')->post('/import', [
            'csv' => UploadedFile::fake()->createWithContent('produk.csv', $csv),
        ])->assertSessionHas('success', '0 produk baru, 501 diperbarui.');
        $this->assertDatabaseCount('products', 501);
    }

    public function test_excel_import_rejects_missing_file_and_incorrect_headers(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post('/import/excel')->assertSessionHasErrors('csv');

        foreach (['', "name;category_id;price\nKopi;1;15000", "name,price\nKopi,15000"] as $csv) {
            $this->post('/import/excel', [
                'csv' => UploadedFile::fake()->createWithContent('produk.csv', $csv),
            ])->assertSessionHasErrors('csv');
        }

        $this->assertDatabaseCount('products', 0);
    }

    public function test_excel_import_is_only_accessible_to_admins(): void
    {
        $this->post('/import/excel')->assertRedirect('/login');

        foreach (['kasir', 'manager'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->post('/import/excel')->assertForbidden();
        }
    }
}
