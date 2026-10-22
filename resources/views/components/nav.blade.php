
<nav class="flex items-center gap-4 bg-slate-900 px-4 py-3 text-white">
    <span class="font-semibold">Simple POS</span>

    <a href="{{ route('pos.create') }}" class="hover:underline">
        Kasir
    </a>

    @if (auth()->user()?->isAdmin() || auth()->user()?->isManager())
        <a href="{{ route('transactions.index') }}" class="hover:underline">
            Transaksi
        </a>
    @endif

    @if (auth()->user()?->isAdmin())
        <a href="{{ route('products.index') }}" class="hover:underline">
            Produk
        </a>

        <a href="{{ route('categories.index') }}" class="hover:underline">
            Kategori
        </a>
    @endif

    <span class="ml-auto text-sm">
        {{ auth()->user()?->name }} ({{ auth()->user()?->role }})
    </span>

    <form method="POST" action="{{ route('logout') }}">
        @csrf

        <button type="submit" class="hover:underline">
            Keluar
        </button>
    </form>
</nav>
