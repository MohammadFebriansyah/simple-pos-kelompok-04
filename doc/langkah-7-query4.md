Langkah 7 - Query Listing Transaksi

Query digunakan untuk menampilkan data transaksi dalam rentang tanggal 30 September 2026.

DB::table('transactions')->whereBetween('created_at', ['2026-09-30 00:00:00', '2026-09-30 23:59:59'])->get();

EXPLAIN QUERY PLAN
Untuk memeriksa apakah query menggunakan index yang tersedia, digunakan perintah:

DB::select("EXPLAIN QUERY PLAN SELECT * FROM transactions WHERE created_at BETWEEN '2026-09-30 00:00:00' AND '2026-09-30 23:59:59'");

Kesimpulan : Hasil SCAN transactions menunjukkan bahwa query belum menggunakan index yang tersedia pada tabel transactions, sehingga SQLite melakukan pemindaian terhadap tabel transactions.