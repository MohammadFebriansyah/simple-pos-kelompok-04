@extends('layouts.app')

@section('title', 'Kasir')

@section('content') 
<h1 class="text-lg font-semibold mb-4">Transaksi Kasir</h1>
<div x-data="{
    cart: [],
    selectedProduct: null,
    addToCart(id, name, price) {
        this.cart.push({ id, name, price });
    },
    subtotal() {
        return this.cart.reduce((sum, item) => sum + item.price, 0);
    }
}">
    
    <div class="grid grid-cols-3 gap-4">
        @foreach ($products as $product)
            <div 
                class="border rounded-md p-3 cursor-pointer"
                :class="selectedProduct === {{ $product->id }} ? 'ring-2 ring-blue-500' : ''"
                @click="selectedProduct = {{ $product->id }}; addToCart({{ $product->id }}, '{{ $product->name }}', {{ $product->price }})"
            >
                <p class="font-medium">{{ $product->name }}</p>
                <p class="text-sm text-slate-500">Rp {{ number_format($product->price) }}</p>

                @if ($product->stock < 10)
                    <span class="bg-amber-100 text-amber-700 px-2 py-1 rounded text-xs">Stok Menipis</span>
                @endif
            </div>
        @endforeach
    </div>

    <div class="mt-4 border-t pt-3">
        <template x-for="item in cart" :key="item.id">
            <p x-text="item.name + ' - Rp ' + item.price"></p>
        </template>
        
        <p class="font-semibold mt-2">
            Subtotal: Rp <span x-text="subtotal()"></span>
        </p>
    </div>
    
</div>
@endsection