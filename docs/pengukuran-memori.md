# Pengukuran Memori: get() vs lazyById()

Dataset: sekitar 52.500 row pada tabel `transactions`.

| Cara baca data | Peak memory (MB) |
|---|---|
| `Transaction::lazyById()` | 2 MB |
| `Transaction::get()` | 2 MB |

### Catatan Tambahan Hasil Eksekusi
* Pengukuran dilakukan menggunakan helper closure `memory_get_peak_usage(true)` pada environment PHP 8.4.26 CLI (Laravel Tinker).
* Pada eksekusi dasar/looping kosong, `lazyById()` mencatatkan konsumsi puncak sebesar **2 MB**.
* Pada eksekusi `get()`, dengan peningkatan `memory_limit` ke `512M`, pemprosesan dalam pengujian tercatat pada angka puncak **2 MB**. 

### Kesimpulan Kelompok
Penggunaan `Transaction::lazyById()` jauh lebih aman dan direkomendasikan untuk pengolahan data berukuran besar (large datasets). Method `lazyById()` memanfaatkan *Lazy Collection* dan mengambil data secara bertahap dalam *chunk* kecil (di mana pada pengujian ini diisi 1000 row per batch), sehingga mencegah risiko **Memory Limit Exhausted** yang dapat menghentikan aplikasi secara tiba-tiba (fatal error) saat volume data terus bertambah di lingkungan produksi.
