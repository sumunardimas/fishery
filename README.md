# Sistem Manajemen Perikanan

> Aplikasi manajemen operasional Perusahaan Perikanan berbasis web, dibangun dengan Laravel 12.

---

## 📄 Overview

- **Backend**: PHP 8.2+, Laravel 12, Eloquent ORM, Spatie Permission
- **Frontend**: Blade, Livewire Flux, Alpine.js, Tailwind CSS v4, Vite
- **Database**: MySQL/MariaDB
- **Testing**: PHPUnit 11

---

## 🚀 Quick Start

1. **Clone & install dependencies**
   ```bash
   git clone <repo-url>
   cd fishery
   composer install
   npm install
   ```

2. **Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   # Set DB_*, MAIL_*, and other values in .env
   ```

3. **Database setup**
   ```bash
   php artisan migrate --seed
   ```

4. **Start server**
   ```bash
   npm run dev
   php artisan serve
   ```

   Visit `http://localhost:8000`

   Or use Docker:
   ```bash
   docker-compose up -d
   ```

---

## 🔧 Modul

| Modul | Deskripsi |
|---|---|
| **Kapal** | CRUD data kapal |
| **Pelayaran** | Catat keberangkatan & pelaporan hasil (perbekalan, tangkapan, operasional) |
| **Penjualan Ikan** | Transaksi penjualan, riwayat, invoice PDF |
| **Pembelian Barang** | Transaksi & riwayat pembelian, master item |
| **Perbekalan** | Transaksi stok perbekalan, riwayat in/out, master |
| **Operasional Kantor** | Pengeluaran kantor, riwayat, master item |
| **Stok Ikan** | Monitoring stok ikan di gudang |
| **Keuangan** | Kas harian, bank, setoran kas induk, piutang, kas bon pegawai, hutang modal |
| **Laporan** | Laporan penjualan & arus kas |
| **Master Data** | Pelanggan, ikan, ikan tangkapan, operasional |
| **Pengguna** | Manajemen user & role (admin, staff, kasir) |

---

## 🧑‍💻 Development

```bash
php artisan test          # jalankan semua test
vendor/bin/phpunit        # alternatif
vendor/bin/pint           # code style (Laravel Pint / PSR-12)
npm run build             # build aset produksi
```

---

## 📁 Struktur Direktori

```
app/
  Http/Controllers/       # controller per modul
  Models/                 # Eloquent models
  Services/               # business logic
  Helpers/                # helper functions
config/
  menu.php                # konfigurasi sidebar menu
database/
  migrations/
  seeders/
resources/views/          # Blade templates
routes/
  web.php                 # semua route web
  auth.php                # route autentikasi
tests/
  Feature/
  Unit/
```

---

## 🧾 License

MIT License.

---

> _Generated: March 2026_
