<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessProductImport;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::firstOrCreate(
            ['email' => 'admin@pos.test'],
            [
                'name' => 'Admin Kafe',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );
    }

    public function test_reports_page_can_be_rendered(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get('/reports');

        $response->assertStatus(200);
        $response->assertViewIs('reports.index');
        $response->assertSee('Laporan Penjualan');
        $response->assertSee('Total Penjualan');
        $response->assertSee('transaksi');
        $response->assertSee(route('reports.export.csv', [], false));
        $response->assertSee(route('reports.export.xlsx', [], false));
        $response->assertSee(route('reports.export.pdf', [], false));
    }

    public function test_reports_csv_export(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get('/reports/export/csv');

        $response->assertStatus(200);
        $this->assertTrue($response->headers->contains('content-disposition', 'attachment; filename=laporan-penjualan.csv'));
    }

    public function test_reports_pdf_export(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get('/reports/export/pdf');

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'application/pdf'));
    }

    public function test_reports_pdf_export_with_top_products_data(): void
    {
        $admin = $this->createAdmin();

        $category = Category::create(['name' => 'Kopi']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kopi Arabika Spesial',
            'price' => 20000,
            'stock' => 50,
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'user_id' => $admin->id,
            'total' => 40000,
        ]);

        \App\Models\TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'qty' => 2,
            'subtotal' => 40000,
        ]);

        $response = $this->actingAs($admin)->get('/reports/export/pdf');

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'application/pdf'));
    }

    public function test_reports_excel_export(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get('/reports/export/xlsx');

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-disposition'), 'laporan-penjualan.xlsx'));
    }

    public function test_import_feature_still_works_and_dispatches_job(): void
    {
        Queue::fake();

        $admin = $this->createAdmin();

        $csvContent = "name,category_id,price\nKopi Test,1,12000";
        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->actingAs($admin)->post('/import', [
            'csv' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Import sedang diproses di background.');

        Queue::assertPushed(ProcessProductImport::class);
    }
}
