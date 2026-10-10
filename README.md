# HRIS — HRD Management System

Aplikasi Human Resource Information System (HRIS) berbasis **Laravel** dengan Blade + Tailwind CSS + Alpine.js + Vite, terhubung ke **MySQL**, memiliki autentikasi, RBAC, dan dashboard berbasis role.

## Prasyarat

- PHP >= 8.2 (diuji dengan PHP 8.3)
- Composer
- Node.js + npm
- MySQL 8 (Laragon / XAMPP / standalone)
- Git

## Instalasi

```sh
cp .env.example .env
composer install
npm install
php artisan key:generate
npm run build
```

## Konfigurasi `.env`

```ini
APP_NAME="HRIS - HRD Management System"
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=HRDManagementSystem
DB_USERNAME=root
DB_PASSWORD=
```

Jangan menaruh kredensial database di source code selain `.env` lokal (sudah di-ignore oleh Git).

## Membuat database MySQL

```sql
CREATE DATABASE HRDManagementSystem CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Migrasi & seeder

```sh
php artisan migrate --seed
```

Untuk reset penuh:

```sh
php artisan migrate:fresh --seed
```

## Menjalankan server lokal

```sh
composer run dev
```

atau manual:

```sh
php artisan serve
npm run dev   # terminal terpisah untuk hot reload
```

Buka `http://localhost:8000`.

## Menjalankan test

```sh
php artisan test
```

Test memakai SQLite in-memory (lihat `phpunit.xml`), aman tanpa menyentuh MySQL.

## Akun demo (development)

Semua password: `password`

| Email | Role |
|---|---|
| superadmin@hris.test | Super Admin |
| hrd@hris.test | Admin HRD |
| manager@hris.test | Manager |
| karyawan@hris.test | Karyawan |

## Status modul

- ✅ P0 Fondasi: auth Breeze, RBAC (spatie), middleware akun aktif, layout sidebar+topbar, komponen Blade, dashboard per role, migrasi + seeder lengkap.
- ✅ P1 Core HR: CRUD karyawan, departemen, jabatan + policy authorization + search/filter/pagination + foto profil privat + provisioning akun login.
- ✅ P2 Absensi: check-in/check-out (timestamp server, anti check-in ganda, shift malam), rekap harian/bulanan/rentang + filter, koreksi absensi + approval, notifikasi in-app.
- ✅ P3 Cuti & Approval: jenis cuti, saldo + ledger (`LeaveBalanceTransaction`), pengajuan + lampiran, approval workflow (transaksi + locking), pembatalan + refund, notifikasi in-app.
- ✅ P4 Laporan & QA: laporan karyawan/absensi/cuti + filter + ekspor CSV, audit log (Super Admin) dengan integrasi aksi penting, 85 feature test.
- ✅ P5 Pengguna & Role: CRUD akun, assign role, aktif/nonaktif (blokir login akun nonaktif), reset password, halaman role & permission — khusus Super Admin. Total 96 feature test.

## Akun & akses audit

Audit log hanya dapat diakses role `super_admin`. HRD mengakses laporan tanpa audit log.

## Deployment production

1. `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` sesuai domain.
2. Selalu akses lewat HTTPS.
3. `php artisan migrate --force` (jangan `migrate:fresh`).
4. `npm run build` untuk aset produksi.
5. Siapkan backup & pemulihan database berkala.
