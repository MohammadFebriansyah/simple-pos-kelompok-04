<?php

namespace Tests\Feature;

use App\Http\Controllers\ReportController;
use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_reports_and_download_all_export_formats(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get('/reports')->assertOk();
        $this->get('/reports/export/csv')->assertOk()->assertDownload('laporan-penjualan.csv');
        $this->get('/reports/export/xlsx')->assertOk()->assertDownload('laporan-penjualan.xlsx');
        $this->get('/reports/export/pdf')->assertOk()->assertDownload('laporan-penjualan.pdf');
    }

    public function test_pdf_report_contains_top_selling_products(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $category = new Category();
        $category->name = 'Minuman';
        $category->save();

        $productA = Product::create([
            'category_id' => $category->id,
            'name' => 'Kopi Susu',
            'price' => 15000,
            'stock' => 100,
        ]);

        $productB = Product::create([
            'category_id' => $category->id,
            'name' => 'Teh Manis',
            'price' => 5000,
            'stock' => 100,
        ]);

        $t1 = Transaction::create([
            'user_id' => $admin->id,
            'total' => 45000,
            'created_at' => now(),
        ]);

        TransactionDetail::create([
            'transaction_id' => $t1->id,
            'product_id' => $productA->id,
            'qty' => 3,
            'subtotal' => 45000,
        ]);

        $t2 = Transaction::create([
            'user_id' => $admin->id,
            'total' => 50000,
            'created_at' => now(),
        ]);

        TransactionDetail::create([
            'transaction_id' => $t2->id,
            'product_id' => $productB->id,
            'qty' => 10,
            'subtotal' => 50000,
        ]);

        $method = new ReflectionMethod(ReportController::class, 'topProducts');
        $topProducts = $method->invoke(new ReportController(), now()->startOfMonth(), now()->endOfDay());

        $this->assertCount(2, $topProducts);
        $this->assertEquals('Teh Manis', $topProducts->first()->name);
        $this->assertEquals(10, $topProducts->first()->total_qty);
        $this->assertEquals('Kopi Susu', $topProducts->last()->name);
        $this->assertEquals(3, $topProducts->last()->total_qty);

        $view = view('reports.pdf', [
            'totalPenjualan' => 95000,
            'jumlahTransaksi' => 2,
            'transactions' => Transaction::with('user')->get(),
            'topProducts' => $topProducts,
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->endOfDay()->toDateString(),
        ])->render();

        $this->assertStringContainsString('Produk Terlaris', $view);
        $this->assertStringContainsString('Teh Manis', $view);
        $this->assertStringContainsString('Kopi Susu', $view);

        $response = $this->get('/reports/export/pdf');
        $response->assertOk();
        $response->assertDownload('laporan-penjualan.pdf');
    }

    public function test_reports_can_be_filtered_by_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $minuman = new Category();
        $minuman->name = 'Minuman';
        $minuman->save();

        $makanan = new Category();
        $makanan->name = 'Makanan';
        $makanan->save();

        $kopi = Product::create(['category_id' => $minuman->id, 'name' => 'Kopi', 'price' => 15000, 'stock' => 100]);
        $roti = Product::create(['category_id' => $makanan->id, 'name' => 'Roti', 'price' => 10000, 'stock' => 100]);

        // Transaksi campuran: 1 kopi + 2 roti
        $t1 = Transaction::create(['user_id' => $admin->id, 'total' => 35000]);
        TransactionDetail::create(['transaction_id' => $t1->id, 'product_id' => $kopi->id, 'qty' => 1, 'subtotal' => 15000]);
        TransactionDetail::create(['transaction_id' => $t1->id, 'product_id' => $roti->id, 'qty' => 2, 'subtotal' => 20000]);

        // Transaksi hanya roti
        $t2 = Transaction::create(['user_id' => $admin->id, 'total' => 10000]);
        TransactionDetail::create(['transaction_id' => $t2->id, 'product_id' => $roti->id, 'qty' => 1, 'subtotal' => 10000]);

        $this->get('/reports')
            ->assertOk()
            ->assertViewHas('totalPenjualan', 45000)
            ->assertViewHas('jumlahTransaksi', 2);

        $this->get('/reports?category_id='.$minuman->id)
            ->assertOk()
            ->assertViewHas('totalPenjualan', 15000)
            ->assertViewHas('jumlahTransaksi', 1)
            ->assertViewHas('categoryId', $minuman->id);

        $this->get('/reports?category_id='.$makanan->id)
            ->assertViewHas('totalPenjualan', 30000)
            ->assertViewHas('jumlahTransaksi', 2);

        $method = new ReflectionMethod(ReportController::class, 'topProducts');
        $top = $method->invoke(new ReportController(), now()->startOfMonth(), now()->endOfDay(), $minuman->id);
        $this->assertCount(1, $top);
        $this->assertEquals('Kopi', $top->first()->name);

        $this->get('/reports/export/csv?category_id='.$minuman->id)->assertOk()->assertDownload('laporan-penjualan.csv');
        $this->get('/reports/export/xlsx?category_id='.$minuman->id)->assertOk()->assertDownload('laporan-penjualan.xlsx');
        $this->get('/reports/export/pdf?category_id='.$minuman->id)->assertOk()->assertDownload('laporan-penjualan.pdf');

        $this->get('/reports?category_id=99999')->assertSessionHasErrors('category_id');
    }
}
