@extends('layouts.app')

@section('title', 'Import Produk')

@section('content')
<h1 class="text-lg font-semibold mb-4">Import Produk dari CSV</h1>

@if (session('success'))
    <div class="bg-green-50 text-green-700 p-3 rounded-md mb-4">
        {{ session('success') }}
    </div>
@endif

<form method="POST" action="{{ route('import.store') }}" enctype="multipart/form-data" class="max-w-md">
    @csrf
    <label class="block mb-3">
        <span class="text-sm font-medium">File CSV</span>
        <input type="file" name="csv" accept=".csv" class="mt-1 block w-full text-sm">
        @error('csv')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </label>
    <p class="text-xs text-slate-500 mb-3">Column yang dibutuhkan: <code>name</code>, <code>category_id</code>, <code>price</code>. Gunakan pemisah koma.</p>
    <div class="flex gap-3">
        <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-md text-sm">Import Manual</button>
        <button type="submit" formaction="{{ route('import.excel.store') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm">Import Laravel Excel</button>
    </div>
</form>

{{-- DITAMBAHKAN DI SINI (Tabel Log Error Import): --}}
@if (isset($importErrors) && $importErrors->count() > 0)
    <div class="mt-6 border-t pt-4">
        <h2 class="text-md font-semibold text-red-600 mb-3">Daftar Baris yang Gagal Di-import</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse border border-slate-200">
                <thead>
                    <tr class="bg-slate-100">
                        <th class="border border-slate-200 px-3 py-2 text-left">Baris Ke</th>
                        <th class="border border-slate-200 px-3 py-2 text-left">Pesan Error</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($importErrors as $error)
                        <tr>
                            <td class="border border-slate-200 px-3 py-2 font-medium">{{ $error->row_number }}</td>
                            <td class="border border-slate-200 px-3 py-2 text-red-600">{{ $error->error_message }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection