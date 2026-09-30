<?php

namespace App\Http\Controllers;
use App\Models\Product;
use Illuminate\Http\Request;

use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function create()
    {
        $products = Product::take(12)->get();

        return view('pos.create', ['products' => $products]);
    }

    public function store(Request $request)
    {
        return 'Transaksi disimpan (belum ada logika penyimpanan)';
    }

        public function index()
    {
        return 'Daftar transaksi';
    }

    public function show(string $id)
    {
        return "Detail transaksi #{$id}";
    }
}