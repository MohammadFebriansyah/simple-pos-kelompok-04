<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin-top: 18px; margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; margin-bottom: 12px; }
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
    <table>
        <thead>
            <tr>
                <th style="width: 30px;" class="center">No</th>
                <th>Nama Produk</th>
                <th class="right" style="width: 90px;">Terjual (Qty)</th>
                <th class="right" style="width: 130px;">Total Penjualan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($topProducts as $index => $item)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $item->name }}</td>
                    <td class="right">{{ number_format($item->total_qty) }}</td>
                    <td class="right">Rp {{ number_format($item->total_sales) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="center">Tidak ada data produk pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>50 Transaksi Terbaru</h2>
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
            @foreach ($transactions as $t)
                <tr>
                    <td>{{ $t->id }}</td>
                    <td>{{ $t->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $t->user->name }}</td>
                    <td class="right">Rp {{ number_format($t->total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
