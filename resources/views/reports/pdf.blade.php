<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin-top: 18px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #999; padding: 4px 8px; text-align: left; }
        th { background: #eee; }
        .right { text-align: right; }
        .center { text-align: center; }
    </style>
</head>
<body>
    <h1>Laporan Penjualan</h1>
    <p>Periode {{ $from }} s.d. {{ $to }}</p>
    <p>Total penjualan: <strong>Rp {{ number_format($totalPenjualan) }}</strong> dari {{ $jumlahTransaksi }} transaksi</p>

    <h2>Produk Terlaris</h2>
    <p>10 produk terlaris pada periode ini:</p>
    <table>
        <thead>
            <tr>
                <th class="center" style="width: 5%;">No</th>
                <th>Nama Produk</th>
                <th class="right" style="width: 20%;">Jumlah Terjual</th>
                <th class="right" style="width: 25%;">Total Penjualan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($topProducts as $index => $item)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td>{{ $item->name }}</td>
                <td class="right">{{ number_format($item->total_qty) }}</td>
                <td class="right">Rp {{ number_format($item->total_subtotal) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="center">Tidak ada data produk terjual pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <h2>Transaksi Terbaru</h2>
    <p>50 transaksi terbaru pada periode ini:</p>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tanggal</th>
                <th>Kasir</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions as $t)
            <tr>
                <td>{{ $t->id }}</td>
                <td>{{ $t->created_at->format('Y-m-d H:i') }}</td>
                <td>{{ $t->user?->name ?? '-' }}</td>
                <td class="right">Rp {{ number_format($t->total) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="center">Tidak ada transaksi pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
