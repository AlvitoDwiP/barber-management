# Barber Management

Aplikasi Laravel untuk owner barbershop yang ingin mencatat transaksi harian, menghitung komisi, mengelola payroll, mencatat pengeluaran, memantau stok produk, dan membaca laporan usaha dari satu tempat.

## Apa yang Aplikasi Ini Lakukan

Fokus utama aplikasi ini adalah operasional owner tunggal:

- input transaksi harian secara batch setelah toko tutup
- kelola master data pegawai, layanan, produk, dan komisi
- hitung komisi barber dari snapshot item transaksi
- buka dan tutup payroll pegawai tetap
- settle pembayaran freelance melalui modul payroll freelance + expense
- catat pengeluaran operasional
- lihat laporan harian, bulanan, metode pembayaran, produk, dan pegawai
- export laporan tertentu ke CSV

## Cara Kerja Singkat

Alur bisnis yang dipakai aplikasi ini:

1. Owner mencatat transaksi di lapangan secara manual saat toko buka.
2. Setelah operasional selesai, owner input transaksi hari itu lewat batch input.
3. Sistem menyimpan setiap transaksi sebagai histori terpisah, menghitung total, komisi, dan stok produk.
4. Owner mencatat pengeluaran yang relevan.
5. Untuk pegawai tetap, owner membuka lalu menutup payroll per periode.
6. Owner membaca laporan dan export data bila perlu.

## Aturan Penting

- Aplikasi ini dirancang untuk satu owner, bukan multi-role.
- Input transaksi utama adalah batch harian, bukan POS real-time.
- `transaction_items` dipakai sebagai snapshot historis. Perubahan master layanan, produk, pegawai, atau komisi tidak boleh merusak histori lama.
- Payroll tetap memakai periode `open` dan `closed`.
- Transaksi yang sudah masuk payroll tertutup tidak boleh diubah lewat flow operasional normal.
- Produk mengurangi stok otomatis saat terjual.

## Modul Utama

- `Dashboard`
- `Transaksi`
- `Payroll`
- `Payroll Freelance`
- `Pegawai`
- `Layanan`
- `Produk`
- `Pengeluaran`
- `Laporan`
- `Pengaturan Komisi`

## Setup Lokal Cepat

Prasyarat:

- PHP 8.2+
- Composer
- Node.js + npm
- SQLite atau MySQL

Langkah cepat:

```bash
git clone <repository-url>
cd barber-management
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
composer run dev
```

Env minimal yang penting:

```env
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Jika tidak ingin seed penuh, jalankan:

```bash
php artisan migrate
php artisan db:seed --class=ServiceSeeder
php artisan db:seed --class=ProductSeeder
php artisan db:seed --class=EmployeeSeeder
php artisan db:seed --class=ExpenseCategorySeeder
```

## Setup Akun Owner

- Jika tabel `users` masih kosong, aplikasi akan mengarahkan ke setup owner pertama.
- Route register biasa dinonaktifkan.
- Setelah owner pertama dibuat, login berikutnya dilakukan lewat halaman login normal.

## Menjalankan Aplikasi

Paling praktis:

```bash
composer run dev
```

Alternatif manual:

```bash
php artisan serve
npm run dev
```

Build production assets:

```bash
npm run build
```

## Testing

Jalankan semua test:

```bash
php artisan test
```

Project ini sudah punya coverage untuk area penting seperti:

- auth single-owner
- transaksi batch dan detail transaksi
- expenses
- payroll dan freelance payroll
- laporan dan export
- beberapa regresi bisnis penting

## Struktur Project

```text
app/
  Http/Controllers   # flow per modul
  Http/Requests      # validasi
  Models             # entitas dan relasi
  Services           # logika bisnis inti

resources/views/     # Blade views
routes/web.php       # route utama
tests/Feature/       # feature tests
```

## Catatan Keamanan

- Jangan commit `.env`.
- Gunakan flow setup owner pertama untuk akun awal.
- Di production, pakai `APP_DEBUG=false`.
- Jika memakai HTTPS, aktifkan `SESSION_SECURE_COOKIE=true`.
- Backup database secara berkala.

## Batasan Saat Ini

- Belum mendukung multi-user dengan role terpisah.
- Belum fokus ke transaksi real-time saat toko buka.
- Export saat ini berfokus pada CSV, belum PDF.
- Workflow stok masih fokus ke pengurangan stok akibat penjualan.
- Audit log penuh belum tersedia.

## Handoff Cepat untuk Developer

Kalau Anda baru masuk ke project ini, pahami area berikut dulu:

1. `app/Services/TransactionService.php`
2. `app/Services/PayrollService.php`
3. `app/Services/FreelancePaymentService.php`
4. `app/Http/Controllers/ReportController.php`
5. `resources/views/transactions/`
6. `tests/Feature/`

Prioritas teknis yang harus dijaga:

- histori transaksi berbasis snapshot
- konsistensi payroll open/close
- sinkronisasi laporan dengan transaksi dan expense
- keamanan flow auth single-owner
