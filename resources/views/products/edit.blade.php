@extends('layouts.app')

@section('title', 'Edit Produk')

@section('content')
<h1 class="text-lg font-semibold mb-4">Edit Produk #{{ $product->id }}</h1>

<form method="POST" action="{{ route('products.update', $product->id) }}" class="max-w-md">
    @csrf
    @method('PUT')

    <label class="block mb-3">
        <span class="text-sm font-medium">Nama</span>
        <input type="text" name="name" value="{{ old('name', $product->name) }}" class="mt-1 block w-full rounded-md border-gray-300">
        @error('name')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>

    <label class="block mb-3">
        <span class="text-sm font-medium">Kategori</span>
        <select name="category_id" class="mt-1 block w-full rounded-md border-gray-300">
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        @error('category_id')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>

    <label class="block mb-3">
        <span class="text-sm font-medium">Harga</span>
        <input type="number" name="price" value="{{ old('price', $product->price) }}" class="mt-1 block w-full rounded-md border-gray-300">
        @error('price')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>

    <label class="block mb-3">
        <span class="text-sm font-medium">Stok</span>
        <input type="number" name="stock" value="{{ old('stock', $product->stock) }}" class="mt-1 block w-full rounded-md border-gray-300">
        @error('stock')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </label>

    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md">Perbarui</button>
</form>
@endsection