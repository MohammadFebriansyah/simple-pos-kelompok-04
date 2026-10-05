<div>
    <nav class="bg-slate-900 text-white px-4 py-3 flex gap-6">
        <span class="font-semibold">Simple POS</span>

        <a href="{{ route('pos.create') }}"
           class="{{ request()->routeIs('pos.create') ? 'font-bold underline' : 'hover:underline' }}">
            Kasir
        </a>

        <a href="{{ route('transactions.index') }}"
           class="{{ request()->routeIs('transactions.index') ? 'font-bold underline' : 'hover:underline' }}">
            Transaksi
        </a>

        <a href="{{ route('products.index') }}"
           class="{{ request()->routeIs('products.index') ? 'font-bold underline' : 'hover:underline' }}">
            Produk
        </a>
    </nav>
</div>