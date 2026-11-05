<?php

namespace App\Http\Controllers;

use App\Exports\SalesReportExport;
use App\Jobs\ProcessProductImport;
use App\Models\Category;
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
        $categoryId = $this->resolveCategory($request);

        $query = $this->filteredQuery($from, $to, $categoryId);

        return view('reports.index', [
            'totalPenjualan' => $this->totalSales($query, $from, $to, $categoryId),
            'jumlahTransaksi' => $query->count(),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'categoryId' => $categoryId,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        [$from, $to] = $this->resolveDateRange($request);
        $categoryId = $this->resolveCategory($request);

        $query = $this->filteredQuery($from, $to, $categoryId);

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
        $categoryId = $this->resolveCategory($request);

        return Excel::download(
            new SalesReportExport($this->filteredQuery($from, $to, $categoryId)),
            'laporan-penjualan.xlsx',
        );
    }

    public function exportPdf(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);
        $categoryId = $this->resolveCategory($request);

        $query = $this->filteredQuery($from, $to, $categoryId);

        $pdf = Pdf::loadView('reports.pdf', [
            'totalPenjualan' => $this->totalSales($query, $from, $to, $categoryId),
            'jumlahTransaksi' => $query->count(),
            'transactions' => (clone $query)->latest()->limit(50)->get(),
            'topProducts' => $this->topProducts($from, $to, $categoryId),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'categoryName' => $categoryId ? Category::find($categoryId)?->name : null,
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

    private function resolveCategory(Request $request): ?int
    {
        $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        return $request->filled('category_id')
            ? $request->integer('category_id')
            : null;
    }

    private function filteredQuery($from, $to, ?int $categoryId = null): Builder
    {
        return Transaction::with('user')
            ->whereBetween('created_at', [$from, $to])
            ->when($categoryId, fn (Builder $q) => $q->whereHas(
                'details.product',
                fn (Builder $p) => $p->where('category_id', $categoryId),
            ));
    }

    /**
     * Tanpa filter kategori: jumlah total transaksi.
     * Dengan filter kategori: hanya subtotal item dari kategori tersebut,
     * agar item kategori lain dalam transaksi yang sama tidak ikut terhitung.
     */
    private function totalSales(Builder $query, $from, $to, ?int $categoryId = null)
    {
        if (! $categoryId) {
            return $query->sum('total');
        }

        return TransactionDetail::query()
            ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_details.product_id', '=', 'products.id')
            ->whereBetween('transactions.created_at', [$from, $to])
            ->where('products.category_id', $categoryId)
            ->sum('transaction_details.subtotal');
    }

    private function topProducts($from, $to, ?int $categoryId = null)
    {
        return TransactionDetail::query()
            ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_details.product_id', '=', 'products.id')
            ->whereBetween('transactions.created_at', [$from, $to])
            ->when($categoryId, fn ($q) => $q->where('products.category_id', $categoryId))
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