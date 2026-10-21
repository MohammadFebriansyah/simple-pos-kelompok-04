# Pengukuran Memori: get() vs lazyById()

Dataset: sekitar 52.500 row pada tabel `transactions`.

`Transaction::lazyById()` :  53.8 MB 
`Transaction::get()` : 112.2 MB 

Kesimpulan kelompok:
Penggunaan `get()` memuat seluruh row transaksi ke dalam RAM sekaligus dalam bentuk Eloquent Collection. Akibatnya konsumsi memori melonjak drastis mencapai 112.2 MB dan berisiko melampaui `memory_limit` PHP pada dataset yang terus bertambah. 

Sebaliknya, `lazyById()` menggunakan teknik chunking (mengambil data sedikit demi sedikit) dengan lazy collection, sehingga penggunaan memori tetap datar dan stabil di angka 53.8 MB (hemat lebih dari 50%). Oleh karena itu, untuk pengolahan dataset besar seperti laporan atau export data ribuan baris, `lazyById()` jauh lebih aman dan efisien dibandingkan `get()`.
