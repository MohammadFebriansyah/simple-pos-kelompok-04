<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
