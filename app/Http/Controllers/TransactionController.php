<?php

namespace App\Http\Controllers;
use App\Models\Transaction;
use App\Models\Product;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function create()
    {
        $products = Product::where('stock', '>', 0)->get();

        return view('pos.create', ['products' => $products]);
    }

    public function store(Request $request)
    {
        return 'Transaksi disimpan (belum ada logika penyimpanan)';
    }

    public function index()
    {
        $transactions = Transaction::with('details.product')
            ->latest()
            ->paginate(15);
        return view('transactions.index', compact('transactions'));
    }

    public function show(string $id)
    {
        return "Detail transaksi #{$id}";
    }
}