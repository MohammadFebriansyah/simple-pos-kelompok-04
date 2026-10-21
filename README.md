# Simple POS - Kelompok 02 (SIB 3E)

Aplikasi Point of Sale (POS) berbasis web menggunakan Laravel 11 dengan sistem otorisasi dan kontrol akses berbasis peran (*Role-Based Access Control*).

---

## Akun Uji Coba (Demo Accounts)

Data akun berikut telah disediakan melalui database seeder (`DemoUserSeeder`):

| Peran (Role) | Email | Password | Hak Akses Utama |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@pos.test` | `password` | Mengakses Kasir, Transaksi, serta mengelola Produk & Kategori |
| **Kasir** | `kasir@pos.test` | `password` | Mengakses Kasir & melihat riwayat Transaksi (terbatas dari menu Admin) |
| **Guest (Tamu)** | *(Tanpa Login)* | - | Mengakses halaman Login (`/login`) & Halaman Informasi (`/info`) |

---

## Skenario Uji Manual Berdasarkan Peran

Berikut adalah skenario pengujian manual untuk setiap peran yang ada pada aplikasi:

### 1. Skenario Uji: Peran Admin (`role: admin`)
* **Tujuan**: Memastikan pengguna dengan peran Admin memiliki akses penuh ke seluruh fitur aplikasi, termasuk manajemen master data (Kategori & Produk).
* **Akun Uji**: `admin@pos.test` / `password`

**Langkah-langkah:**
1. Buka browser dan akses halaman login di `http://localhost:8000/login`.
2. Masukkan email `admin@pos.test` dan password `password`, kemudian klik tombol **Login**.
3. Periksa bilah navigasi (navbar) di bagian atas layar:
   - Periksa identitas pengguna yang ditampilkan pada sudut kanan navbar.
   - Periksa ketersediaan tautan menu navigasi.
4. Klik menu **Produk** (`/products`) atau buka halaman tambah produk di `/products/create`.
5. Klik menu **Kategori** (`/categories`) atau coba tambahkan kategori baru.
6. Klik menu **Kasir** (`/pos`) dan lakukan simulasi transaksi.
7. Klik tombol **Keluar** untuk mengakhiri sesi.

**Hasil yang Diharapkan:**
- Pengguna berhasil login dan diarahkan ke halaman kasir POS (`/pos`).
- Bilah navigasi menampilkan teks identitas `Admin Kafe (admin)`.
- Semua menu navigasi ditampilkan: **Kasir**, **Transaksi**, **Produk**, dan **Kategori**.
- Halaman `/products` dan `/categories` dapat diakses dengan status HTTP 200 (tanpa error 403 Forbidden).
- Admin dapat melakukan operasi pengelolaan produk dan kategori secara normal.
- Sesi berhasil diakhiri setelah menekan tombol Keluar dan pengguna kembali ke halaman login.

---

### 2. Skenario Uji: Peran Kasir (`role: kasir`)
* **Tujuan**: Memastikan pengguna dengan peran Kasir dapat mengakses transaksi kasir, namun **dibatasi dan ditolak** ketika mencoba mengakses halaman manajemen admin (Produk & Kategori).
* **Akun Uji**: `kasir@pos.test` / `password`

**Langkah-langkah:**
1. Buka browser dan akses halaman login di `http://localhost:8000/login`.
2. Masukkan email `kasir@pos.test` dan password `password`, kemudian klik tombol **Login**.
3. Periksa bilah navigasi (navbar) di bagian atas layar:
   - Periksa identitas pengguna yang ditampilkan.
   - Periksa ketersediaan tautan menu navigasi.
4. Lakukan pembuatan transaksi pada halaman **Kasir** (`/pos`) dan periksa riwayat pada halaman **Transaksi** (`/transactions`).
5. Coba akses rute manajemen produk secara langsung melalui address bar browser: ketik `http://localhost:8000/products` lalu tekan Enter.
6. Coba akses rute manajemen kategori secara langsung melalui address bar browser: ketik `http://localhost:8000/categories` lalu tekan Enter.
7. Klik tombol **Keluar** untuk mengakhiri sesi.

**Hasil yang Diharapkan:**
- Pengguna berhasil login dan diarahkan ke halaman kasir POS (`/pos`).
- Bilah navigasi menampilkan teks identitas `Kasir Kafe (kasir)`.
- Bilah navigasi **hanya** menampilkan menu **Kasir** dan **Transaksi**. Menu **Produk** dan **Kategori** tidak muncul (disembunyikan).
- Halaman `/pos` dan `/transactions` dapat diakses dan digunakan secara normal untuk transaksi.
- Saat mengakses `http://localhost:8000/products` atau `http://localhost:8000/categories`, sistem menolak akses dengan menampilkan error **HTTP 403 Forbidden** bertuliskan:  
  `"Halaman ini hanya untuk peran admin."`
- Rute admin berhasil dilindungi oleh middleware `role:admin`.

---

### 3. Skenario Uji: Peran Guest / Tamu (`guest`)
* **Tujuan**: Memastikan pengguna yang belum login tidak dapat mengakses halaman transaksi atau manajemen, serta hanya diizinkan mengakses halaman publik/tamu.
* **Akun Uji**: Pengguna belum terautentikasi (gunakan tab *Incognito/Private Browsing*).

**Langkah-langkah:**
1. Buka tab baru dalam mode *Incognito* / *Private Browsing* (kondisi belum login).
2. Coba akses halaman kasir `http://localhost:8000/pos` atau riwayat transaksi `http://localhost:8000/transactions`.
3. Coba akses halaman manajemen produk `http://localhost:8000/products` atau kategori `http://localhost:8000/categories`.
4. Buka halaman informasi tamu di `http://localhost:8000/info`.
5. Buka halaman login di `http://localhost:8000/login`.

**Hasil yang Diharapkan:**
- Setiap upaya mengakses halaman `/pos`, `/transactions`, `/products`, maupun `/categories` akan secara otomatis dialihkan (*redirect*) ke halaman `/login`.
- Halaman publik seperti `/info` dan `/login` dapat diakses dengan sukses tanpa hambatan autentikasi.