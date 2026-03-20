# Barber Management

## Deskripsi Singkat Aplikasi

Barber Management adalah aplikasi web berbasis Laravel untuk membantu owner barbershop mencatat transaksi harian, mengelola master data operasional, menghitung komisi barber, memproses payroll, mencatat pengeluaran, memantau stok produk, dan menghasilkan laporan bisnis berkala.

Aplikasi ini dirancang untuk pola kerja owner tunggal. Selama operasional berjalan, transaksi tetap dicatat manual di buku atau catatan lapangan. Setelah toko tutup, owner menginput seluruh transaksi hari tersebut ke aplikasi melalui alur input batch harian agar rekap bisnis, komisi, payroll, dan laporan tetap tersusun rapi.

## Latar Belakang Bisnis / Masalah yang Diselesaikan

Operasional barbershop sering berjalan cepat dan fokus utama berada pada pelayanan pelanggan. Dalam kondisi seperti ini, pencatatan digital real-time sering tidak praktis untuk owner tunggal. Dampaknya:

- Rekap pemasukan harian rawan tercecer karena tersebar di catatan manual.
- Komisi barber dan payroll rawan salah hitung jika dikerjakan manual.
- Penjualan produk dan pengeluaran sulit dikonsolidasikan dalam satu laporan.
- Owner kesulitan melihat performa bulanan bisnis secara cepat dan konsisten.

Aplikasi ini menyelesaikan masalah tersebut dengan mengubah catatan manual harian menjadi data terstruktur yang bisa dipakai untuk operasional administrasi, penggajian, dan evaluasi bisnis.

## Tujuan Aplikasi

- Menyediakan sistem pencatatan transaksi harian yang sesuai dengan kebiasaan operasional owner.
- Mengurangi perhitungan manual untuk komisi barber dan payroll.
- Menyatukan data layanan, produk, pengeluaran, dan laporan dalam satu sistem.
- Menyediakan histori transaksi yang lebih rapi dan mudah ditelusuri.
- Mempermudah owner membaca performa usaha per hari, per bulan, per produk, dan per pegawai.

## Catatan Bisnis Penting

- Aplikasi ini dirancang untuk satu pengguna utama, yaitu owner.
- Alur input transaksi bukan real-time POS saat toko sedang ramai, melainkan input batch setelah operasional selesai.
- Catatan manual harian tetap menjadi sumber pencatatan lapangan selama toko buka.
- Tanggal transaksi menjadi dasar penting untuk laporan, komisi, dan payroll, sehingga owner perlu disiplin memasukkan transaksi ke tanggal yang benar.

## Fitur Utama

- Dashboard ringkas untuk melihat performa hari ini dan bulan berjalan.
- Input batch transaksi harian dalam satu form untuk beberapa transaksi sekaligus.
- Pengelolaan master pegawai, layanan, produk, pengeluaran, dan pengaturan komisi.
- Perhitungan komisi barber berbasis snapshot per item transaksi.
- Payroll pegawai tetap berbasis periode open dan close.
- Settlement komisi pegawai freelance per tanggal kerja melalui modul payroll freelance dan pengeluaran.
- Laporan harian, bulanan, metode pembayaran, penjualan produk, dan kinerja pegawai.
- Ekspor laporan tertentu ke format CSV.
- Pencatatan stok produk yang berkurang otomatis saat produk terjual.

## Alur Penggunaan Utama Owner

1. Selama toko beroperasi, owner mencatat transaksi layanan, penjualan produk, dan catatan lain secara manual di buku.
2. Setelah barbershop tutup, owner membuka halaman input harian transaksi.
3. Owner memilih tanggal transaksi, lalu memasukkan beberapa blok transaksi sekaligus.
4. Setiap blok transaksi diisi dengan satu pegawai transaksi, metode pembayaran, catatan opsional, serta item layanan dan atau produk.
5. Setelah data disimpan, sistem membentuk histori transaksi terpisah untuk setiap blok, menghitung subtotal, total, komisi item, dan mengurangi stok produk bila ada penjualan produk.
6. Owner mencatat pengeluaran harian yang relevan, termasuk pembayaran freelance jika ada.
7. Untuk pegawai tetap, owner membuka periode payroll dan menutup payroll ketika periode selesai agar hasil payroll tersimpan sebagai snapshot.
8. Owner membuka laporan untuk memantau omzet, pengeluaran, komisi, performa pegawai, dan hasil bisnis bulanan.

## Modul Utama Sistem

- `Dashboard`: ringkasan hari ini, ringkasan bulan berjalan, pegawai paling produktif, dan produk paling laku.
- `Transaksi`: input batch harian, daftar histori transaksi, detail transaksi, dan hapus transaksi yang masih diperbolehkan.
- `Payroll`: pengelolaan periode payroll pegawai tetap, termasuk open, review, dan close payroll.
- `Payroll Freelance`: rekap komisi harian pegawai freelance dan proses penyiapan pembayaran.
- `Pegawai`: data barber atau pegawai, termasuk tipe permanent atau freelance dan status aktif.
- `Layanan`: master layanan beserta harga dan opsi aturan komisi.
- `Produk`: master produk, harga, stok, dan aturan komisi.
- `Pengeluaran`: pencatatan biaya operasional dan settlement pembayaran freelance.
- `Laporan`: laporan harian, bulanan, metode pembayaran, penjualan produk, dan kinerja pegawai.
- `Pengaturan Komisi`: pengaturan default komisi layanan dan produk.
- `Profil`: perubahan data akun dan password user yang digunakan.

## Entitas / Data Utama

- `User`: akun yang dipakai untuk login ke sistem.
- `Employee`: data pegawai beserta tipe kerja `permanent` atau `freelance`.
- `Service`: master layanan dan harga layanan.
- `Product`: master produk, harga jual, stok, dan aturan komisi produk.
- `Transaction`: header transaksi harian.
- `TransactionItem`: item detail transaksi yang menyimpan snapshot nama item, harga, pegawai, dan komisi pada saat transaksi dibuat.
- `PayrollPeriod`: periode payroll pegawai tetap dengan status `open` atau `closed`.
- `PayrollResult`: hasil rekap payroll yang dibekukan ketika payroll ditutup.
- `FreelancePayment`: ringkasan komisi freelance per tanggal kerja dan status pembayarannya.
- `Expense`: pengeluaran operasional dan pengeluaran settlement freelance.
- `CommissionSetting`: pengaturan default komisi layanan dan produk.
- `StockMovement`: tabel pergerakan stok tersedia di database, tetapi belum menjadi alur operasional utama yang lengkap pada UI saat ini.

## Arsitektur Singkat

Project ini mengikuti pola Laravel yang cukup jelas dan mudah di-handoff:

- `Controllers` menangani request HTTP, pengambilan filter, dan pengiriman data ke view.
- `Form Requests` menangani validasi input agar aturan data tetap konsisten.
- `Services` menangani logika bisnis inti seperti transaksi, payroll, komisi, freelance settlement, dan laporan.
- `Models` merepresentasikan entitas database dengan relasi Eloquent.
- `Blade Views` dan `Alpine.js` dipakai untuk antarmuka admin yang ringan dan cepat.
- `transaction_items` dipakai sebagai snapshot historis agar laporan dan payroll tetap stabil walaupun master layanan, produk, pegawai, atau aturan komisi berubah di kemudian hari.

Pendekatan snapshot ini penting: nilai komisi, harga, nama item, dan identitas pegawai yang dipakai untuk payroll serta laporan diambil dari data transaksi pada saat disimpan, bukan selalu dihitung ulang dari master terbaru.

## Aturan Bisnis Penting

### Transaksi Harian

- Alur input transaksi yang aktif untuk pengguna adalah input batch harian.
- Dalam satu kali submit, owner bisa memasukkan beberapa blok transaksi untuk satu tanggal yang sama.
- Setiap blok disimpan sebagai transaksi terpisah agar histori tetap rapi.
- Setiap blok transaksi memiliki satu pegawai transaksi dan satu metode pembayaran.
- Metode pembayaran yang tersedia saat ini adalah `cash` dan `qr`.
- Item transaksi dapat berupa layanan atau produk.
- Qty layanan harus selalu `1`.
- Qty produk minimal `1`.

### Input Batch Setelah Toko Tutup

- Form ini memang dirancang untuk dipakai setelah operasional selesai, bukan untuk input kasir real-time.
- Satu halaman input dapat memuat beberapa transaksi sekaligus untuk mempercepat entri data akhir hari.
- Jika tanggal transaksi berada pada periode payroll yang sudah ditutup, transaksi tetap bisa tersimpan, tetapi tidak akan mengubah payroll yang sudah ditutup sebelumnya.

### Komisi Barber

- Komisi disimpan per item transaksi sebagai snapshot.
- Komisi layanan mengikuti aturan default atau override pada master layanan.
- Komisi produk mengikuti aturan default atau override pada master produk.
- Komisi layanan default saat ini hanya menerima tipe persen.
- Komisi produk default mendukung persen atau nominal tetap per unit.
- Perubahan pengaturan komisi di masa depan tidak boleh mengubah histori komisi transaksi lama karena histori memakai snapshot.

### Payroll

- Payroll period dibuka dan ditutup secara manual oleh owner.
- Hanya pegawai bertipe `permanent` yang masuk ke payroll period reguler.
- Saat payroll ditutup, sistem membekukan hasil per pegawai ke tabel hasil payroll.
- Transaksi yang sudah terikat ke payroll tertutup tidak dapat diubah atau dihapus melalui alur operasional normal.
- Sistem hanya mengizinkan satu payroll berstatus `open` pada saat yang sama.
- Sistem memberi peringatan bila owner mencoba membuat periode payroll yang overlap dengan periode lain. Overlap sebaiknya dihindari karena berisiko menimbulkan perhitungan ganda.

### Pengeluaran

- Pengeluaran dicatat terpisah dari transaksi pendapatan.
- Kategori pengeluaran operasional saat ini mencakup listrik, beli produk stok, beli alat, bayar freelance, dan lainnya.
- Pembayaran freelance yang disettle melalui modul payroll freelance akan dibuat sebagai pengeluaran dengan keterkaitan ke data settlement.
- Pengeluaran hasil settlement freelance tidak dapat diedit atau dihapus melalui UI biasa agar histori pembayaran tetap konsisten.

### Stok Produk

- Stok produk berkurang otomatis ketika item produk disimpan pada transaksi.
- Sistem akan menolak penyimpanan transaksi bila stok tidak mencukupi.
- Jika transaksi yang masih boleh dihapus dibatalkan, stok produk terkait akan dikembalikan.
- Pengelolaan restock, stock opname, dan histori mutasi stok yang benar-benar lengkap belum menjadi alur utama sistem saat ini.

### Laporan Bulanan

- Laporan bulanan dihitung dari pendapatan layanan, pendapatan produk, total komisi pegawai, dan total pengeluaran.
- Laba bersih bulanan dihitung dari total pendapatan dikurangi komisi pegawai dan pengeluaran.
- Laporan bulanan tersedia per tahun dan dapat diunduh dalam format CSV.
- Selain laporan bulanan, tersedia pula laporan harian, laporan metode pembayaran, laporan penjualan produk, dan laporan kinerja pegawai.

## Role Pengguna

Secara bisnis, aplikasi ini hanya memiliki satu role aktif:

- `Owner / Admin`: mengelola seluruh data, melakukan input transaksi harian, mengelola payroll, mencatat pengeluaran, dan mengakses seluruh laporan.

Catatan implementasi saat ini:

- Belum ada pemisahan role seperti kasir, barber, supervisor, atau finance.
- Self-registration tidak dipakai. Pembuatan akun hanya tersedia melalui flow setup owner pertama saat tabel `users` masih kosong.

## Tampilan / Halaman Utama Sistem

- `Dashboard`: kartu ringkasan pendapatan hari ini, transaksi hari ini, cash, QR, performa bulan berjalan, pegawai terbaik, dan produk terlaris.
- `Input Harian Transaksi`: halaman inti untuk memasukkan beberapa transaksi sekaligus setelah toko tutup.
- `Daftar Transaksi`: histori transaksi dengan filter tanggal, pegawai, dan status payroll.
- `Detail Transaksi`: rincian item layanan atau produk, pegawai, komisi, total, dan status payroll terkait.
- `Payroll`: daftar periode payroll, detail payroll, dan proses close payroll.
- `Payroll Freelance`: rekap komisi harian pegawai freelance dan proses pembayaran.
- `Pegawai`: daftar pegawai aktif atau nonaktif serta tipe kerja.
- `Layanan`: daftar layanan dan harga.
- `Produk`: daftar produk, harga, stok, dan konfigurasi komisi.
- `Pengeluaran`: daftar biaya operasional dan biaya pembayaran freelance.
- `Laporan`: halaman ringkasan akses ke laporan harian, bulanan, metode pembayaran, produk, dan pegawai.
- `Pengaturan Komisi`: pengaturan default komisi layanan dan produk.

## Teknologi yang Digunakan

- PHP 8.2+
- Laravel 11
- Laravel Breeze untuk fondasi autentikasi
- Blade Template untuk server-rendered UI
- Alpine.js untuk interaksi ringan di sisi frontend
- Tailwind CSS untuk styling antarmuka
- Vite untuk asset bundling
- PHPUnit untuk automated testing
- Database Laravel standar

Catatan database:

- `.env.example` saat ini memakai `sqlite` sebagai konfigurasi default lokal.
- Query laporan sudah memuat penyesuaian untuk beberapa driver database, tetapi setup lokal paling cepat biasanya menggunakan SQLite.

## Struktur Project Singkat

```text
app/
  Http/
    Controllers/        # Controller per modul
    Requests/           # Validasi form request
  Models/               # Model dan relasi utama
  Services/             # Logika bisnis transaksi, payroll, komisi, laporan
  Support/              # Helper nilai uang dan utilitas transaksi

database/
  migrations/           # Struktur database
  seeders/              # Seeder data master operasional

resources/
  views/                # Blade views per modul

routes/
  web.php               # Route aplikasi utama
  auth.php              # Route autentikasi

tests/
  Feature/              # Pengujian fitur utama
  Unit/                 # Pengujian utilitas dan helper
```

## Cara Instalasi dan Setup Lokal

### Prasyarat

- PHP `8.2` atau lebih baru
- Composer
- Node.js dan npm
- Database lokal, atau SQLite untuk setup paling cepat

### Langkah Instalasi

```bash
git clone <repository-url>
cd barber-management
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Jika menggunakan SQLite lokal:

```bash
touch database/database.sqlite
```

Kemudian lanjutkan konfigurasi environment dan migrasi database.

## Konfigurasi Environment

Sesuaikan file `.env` minimal dengan parameter berikut:

```env
APP_NAME="APP_NAME"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://APP_URL
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
APP_FALLBACK_LOCALE=id

DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite

# Contoh bila memakai MySQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=DB_DATABASE
# DB_USERNAME=DB_USERNAME
# DB_PASSWORD=DB_PASSWORD

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_SECURE_COOKIE=false
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Rekomendasi penting:

- Gunakan `APP_TIMEZONE=Asia/Jakarta` agar tanggal operasional, payroll, dan laporan sejalan dengan konteks bisnis.
- Gunakan `APP_LOCALE=id` agar tampilan tanggal dan teks pendukung lebih sesuai kebutuhan lokal.
- Jangan commit file `.env` ke repository.

## Migrasi dan Seeding

### Opsi Aman yang Direkomendasikan

Untuk handoff atau setup yang lebih aman, jalankan migrasi lalu seed data master secara selektif:

```bash
php artisan migrate
php artisan db:seed --class=ServiceSeeder
php artisan db:seed --class=ProductSeeder
php artisan db:seed --class=EmployeeSeeder
php artisan db:seed --class=ExpenseCategorySeeder
```

Pendekatan ini cocok bila tim ingin:

- menyiapkan master data dasar tanpa membuat akun default dari repo,
- meninjau ulang data sample sebelum dipakai owner,
- dan membuat akun owner awal melalui flow setup pertama yang lebih aman.

### Opsi Seed Penuh untuk Lokal / Development

```bash
php artisan migrate --seed
```

`DatabaseSeeder` saat ini hanya mengisi data master operasional dasar seperti layanan, produk, pegawai sample, dan kategori pengeluaran. Seeder tidak lagi membuat akun login default.

## Akun Awal / Setup Awal

Flow akun awal yang sekarang dipakai:

1. Jalankan migrasi dan seed master data tanpa kredensial bootstrap yang tidak diperlukan.
2. Akses aplikasi melalui `APP_URL/` atau langsung ke `APP_URL/setup/owner`.
3. Jika tabel `users` masih kosong, aplikasi akan menampilkan halaman `Buat akun owner pertama`.
4. Isi `name`, `email`, `password`, dan `password_confirmation`.
5. Setelah berhasil disimpan, owner akan langsung login ke aplikasi.
6. Flow setup akan otomatis ditutup setelah user pertama terbentuk.

Catatan implementasi saat ini:

- Route `register` sudah dinonaktifkan.
- Route setup owner pertama hanya bisa diakses saat belum ada user sama sekali.
- Jika route setup diakses setelah akun owner tersedia, aplikasi akan mengarahkan user ke login atau dashboard dengan pesan status yang sesuai.
- Login biasa tetap dipakai setelah akun owner pertama selesai dibuat.
- Fitur lupa password tetap dipertahankan, tetapi baru relevan jika akun owner sudah ada dan mailer production sudah dikonfigurasi dengan benar.

## Menjalankan Aplikasi

### Opsi Praktis

```bash
composer run dev
```

Command di atas akan menjalankan server Laravel, listener queue, log tail, dan Vite secara bersamaan.

### Opsi Manual

Terminal 1:

```bash
php artisan serve
```

Terminal 2:

```bash
npm run dev
```

Untuk build asset production:

```bash
npm run build
```

Setelah aplikasi aktif, akses:

```text
APP_URL/
```

Perilaku akses awal:

- Jika belum ada user, aplikasi akan mengarahkan ke halaman setup owner pertama.
- Jika user sudah ada dan belum login, aplikasi akan mengarahkan ke halaman login.
- Jika user sudah login, aplikasi akan langsung mengarah ke dashboard.

## Testing

Menjalankan seluruh test:

```bash
php artisan test
```

Pengujian yang sudah tersedia di project ini mencakup area-area penting seperti:

- autentikasi dan profil user,
- input batch transaksi harian,
- service layer transaksi,
- payroll dan freelance payroll,
- pengaturan komisi,
- laporan harian, bulanan, produk, metode pembayaran, dan pegawai,
- serta beberapa regresi dan utilitas pendukung.

## Catatan Keamanan

- Jangan menyimpan kredensial final owner di repository.
- Tidak ada akun default hardcoded yang boleh dipakai sebagai pola deployment.
- Gunakan flow setup owner pertama untuk membuat akun awal secara aman.
- Gunakan password owner yang kuat sejak setup pertama karena flow setup hanya bisa dipakai sekali.
- Pastikan `APP_ENV=production`, `APP_DEBUG=false`, dan `SESSION_SECURE_COOKIE=true` pada deployment HTTPS production.
- Simpan file `.env`, kredensial database, dan secret key di luar version control.
- Lakukan backup database berkala karena aplikasi ini menyimpan data transaksi, payroll, dan laporan bisnis yang bersifat penting.
- Login throttling bawaan Laravel tetap aktif untuk membatasi percobaan login berulang.
- Session aplikasi sebaiknya tetap memakai driver server-side seperti `database`, bukan konfigurasi eksperimental yang tidak diaudit.

## Batasan Sistem Saat Ini

- Sistem belum dirancang untuk multi-user dengan role dan permission terpisah.
- Alur input transaksi real-time saat toko sedang buka belum menjadi fokus desain; sistem lebih cocok untuk input batch setelah toko tutup.
- UI saat ini belum menyediakan alur edit transaksi penuh; alur operasional utama yang tersedia adalah create batch, lihat detail, dan hapus transaksi yang masih memenuhi syarat.
- Audit log aktivitas pengguna secara penuh belum tersedia.
- Laporan yang dapat diunduh saat ini berfokus pada format CSV, belum PDF.
- Rekonsiliasi kas harian yang formal antara cash fisik, QR, dan saldo akhir belum tersedia sebagai modul khusus.
- Pengelolaan stok masih berfokus pada pengurangan stok akibat penjualan; alur restock, stock opname, dan mutasi stok detail belum lengkap di UI.
- Karena hanya mendukung satu owner, belum ada workflow formal untuk rotasi kepemilikan akun atau approval berlapis.

## Roadmap Pengembangan

Pengembangan berikutnya yang realistis dan bernilai tinggi untuk bisnis:

- Menambahkan fitur edit atau koreksi transaksi dengan jejak revisi yang jelas.
- Menambahkan audit log aktivitas penting seperti login, ubah data master, close payroll, hapus transaksi, dan perubahan komisi.
- Menambahkan ekspor laporan PDF untuk kebutuhan cetak dan handoff ke pihak non-teknis.
- Menambahkan modul rekonsiliasi kas harian antara transaksi tercatat, kas fisik, dan pembayaran non-tunai.
- Menambahkan dashboard operasional yang lebih detail untuk pemantauan performa harian dan bulanan.
- Menambahkan workflow stok yang lebih lengkap, termasuk restock, stock opname, dan histori mutasi.
- Menambahkan recovery flow owner yang lebih terkontrol jika akses akun hilang dan email reset tidak tersedia.
- Menambahkan mekanisme backup dan restore yang lebih eksplisit untuk kebutuhan operasional bisnis.

## Catatan Developer

- Root route disederhanakan menjadi decision point tunggal: setup owner pertama, login, atau dashboard sesuai kondisi user dan session.
- Self-registration Breeze dinonaktifkan agar surface area auth lebih kecil untuk aplikasi single-owner.
- Flow setup owner pertama dikunci oleh middleware khusus berbasis keberadaan data pada tabel `users`.
- Password reset tetap dipertahankan karena implementasi Laravel Breeze sudah stabil, tetapi efektivitasnya tetap bergantung pada konfigurasi mail production.

## Kesimpulan

Barber Management adalah aplikasi administrasi operasional barbershop yang dibangun untuk kebutuhan owner tunggal dengan pola kerja input transaksi setelah operasional selesai. Nilai utama sistem ini ada pada penyatuan transaksi, komisi, payroll, pengeluaran, dan laporan dalam satu alur yang sederhana namun cukup terstruktur untuk kebutuhan bisnis sehari-hari.

Untuk handoff developer berikutnya, fokus utama yang perlu dijaga adalah konsistensi snapshot transaksi, keakuratan logika payroll dan komisi, serta penguatan aspek keamanan dan pembatasan akses. Untuk owner bisnis, aplikasi ini sudah memberi fondasi administrasi yang lebih tertib, dengan ruang pengembangan yang masih terbuka untuk audit, rekonsiliasi, dan pelaporan yang lebih matang.
