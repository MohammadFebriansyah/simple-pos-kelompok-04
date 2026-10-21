@extends('layouts.app')

@section('title', 'Laporan Penjualan')

@section('content')
<div class="p-6 max-w-6xl mx-auto">
    <h1 class="text-lg font-semibold mb-4">Laporan Penjualan</h1>

    <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap gap-3 items-end mb-6">
        <label class="block">
            <span class="text-sm font-medium">Dari</span>
            <input type="date" name="from" value="{{ $from }}" class="mt-1 block border rounded-md px-3 py-2 text-sm">
        </label>

        <label class="block">
            <span class="text-sm font-medium">Sampai</span>
            <input type="date" name="to" value="{{ $to }}" class="mt-1 block border rounded-md px-3 py-2 text-sm">
        </label>

        <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-md text-sm hover:bg-slate-800">Filter</button>
        <a href="{{ route('reports.export.csv', ['from' => $from, 'to' => $to]) }}" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700">Export CSV</a>
        <a href="{{ route('reports.export.xlsx', ['from' => $from, 'to' => $to]) }}" class="bg-green-600 text-white px-4 py-2 rounded-md text-sm hover:bg-green-700">Export Excel</a>
        <a href="{{ route('reports.export.pdf', ['from' => $from, 'to' => $to]) }}" class="bg-red-600 text-white px-4 py-2 rounded-md text-sm hover:bg-red-700">Export PDF</a>
    </form>

    <div class="bg-white border rounded-md p-4 max-w-sm">
        <p class="text-sm text-slate-500">Total Penjualan</p>
        <p class="text-2xl font-semibold">Rp {{ number_format($totalPenjualan) }}</p>
        <p class="text-sm text-slate-500 mt-2">{{ $jumlahTransaksi }} transaksi</p>
    </div>
</div>
@endsection
