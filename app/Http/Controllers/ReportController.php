<?php

namespace App\Http\Controllers;

use App\Exports\SalesReportExport;
use App\Jobs\ProcessProductImport;
use App\Models\ImportError;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to] = $this->resolveDateRange($request);

        $query = $this->filteredQuery($from, $to);

        return view('reports.index', [
            'totalPenjualan' => $query->sum('total'),
            'jumlahTransaksi' => $query->count(),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        [$from, $to] = $this->resolveDateRange($request);

        $query = $this->filteredQuery($from, $to);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['ID', 'Tanggal', 'Kasir', 'Total']);

            foreach ($query->lazyById() as $t) {
                fputcsv($out, [
                    $t->id,
                    $t->created_at,
                    $t->user?->name ?? '-',
                    $t->total,
                ]);
            }

            fclose($out);
        }, 'laporan-penjualan.csv');
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        [$from, $to] = $this->resolveDateRange($request);

        return Excel::download(
            new SalesReportExport($this->filteredQuery($from, $to)),
            'laporan-penjualan.xlsx',
        );
    }

    public function exportPdf(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);

        $query = $this->filteredQuery($from, $to);

        $pdf = Pdf::loadView('reports.pdf', [
            'totalPenjualan' => $query->sum('total'),
            'jumlahTransaksi' => $query->count(),
            'transactions' => $query->latest()->limit(50)->get(),
            'topProducts' => $this->topProducts($from, $to),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);

        return $pdf->download('laporan-penjualan.pdf');
    }

    public function importForm(): View
    {
        $importErrors = ImportError::orderBy('row_number')->get();
        return view('reports.import', compact('importErrors'));
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $path = $request->file('csv')->store('imports');

        ProcessProductImport::dispatch($path);

        return back()->with(
            'success',
            'Import sedang diproses di background.'
        );
    }

    private function resolveDateRange(Request $request): array
    {
        $from = $request->date('from')?->startOfDay()
            ?? now()->startOfMonth();

        $to = $request->date('to')?->endOfDay()
            ?? now()->endOfDay();

        return [$from, $to];
    }

    private function filteredQuery($from, $to): Builder
    {
        return Transaction::with('user')
            ->whereBetween('created_at', [$from, $to]);
    }

    private function topProducts($from, $to)
    {
        return TransactionDetail::query()
            ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_details.product_id', '=', 'products.id')
            ->whereBetween('transactions.created_at', [$from, $to])
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(transaction_details.qty) as total_qty'),
                DB::raw('SUM(transaction_details.subtotal) as total_subtotal')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_qty')
            ->orderByDesc('total_subtotal')
            ->limit(10)
            ->get();
    }
}