# Tantangan Mandiri: Keamanan Total Transaksi

Validasi `numeric` pada field total tidak cukup untuk mencegah manipulasi total. Validasi tersebut hanya memastikan bahwa nilai yang dikirim berupa angka, tetapi tidak memastikan bahwa angka tersebut benar.

Pengguna masih dapat mengubah nilai total pada form, misalnya total yang seharusnya Rp100.000 diubah menjadi Rp1.000. Nilai tersebut tetap lolos validasi karena masih berupa angka.

Oleh karena itu, total transaksi tidak boleh dipercaya dari input client. Total harus dihitung ulang oleh server berdasarkan harga produk yang diambil dari database dan jumlah barang yang dikirim. Dengan cara ini, total yang disimpan tetap sesuai dengan perhitungan sebenarnya meskipun nilai dari client dimanipulasi.