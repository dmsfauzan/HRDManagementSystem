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

## Penyempurnaan (Sprint)

- Sprint A Keamanan & Produksi (Selesai): Git, registrasi publik dinonaktifkan, rate limiting, security headers, error pages berbranding, lokalisasi `lang/id`, hardening env & dokumentasi backup/deploy, audit login-logout & perubahan permission, halaman Role kini editable per permission.
- Sprint B Kelengkapan Fitur (Selesai): PHP Enums (attendance/leave/employment), command `attendance:mark-absent` + scheduler (kartu Tidak Hadir live di dashboard), CRUD Hari Libur & Jadwal Kerja, CRUD Pengumuman HR + broadcast notifikasi, Modul Dokumen HR (privat), self-service profil karyawan, Kalender Cuti, Impor Karyawan CSV + template, sisa saldo saat approval.
- Sprint C/D/E Kualitas, Testing, UI (Selesai): `pint.json`, GitHub Actions CI, seeder demo kaya (15 karyawan + riwayat), unit test service, avatar pengguna, komponen `confirm-modal`.
- **Fase 1A (Selesai):** Pengelolaan sesi (lihat & cabut perangkat), notifikasi email + antrian (`ShouldQueue` + `toMail`), Lembur (Overtime: pengajuan + approval + audit), ekspor laporan **XLSX** (tanpa paket eksternal) & **PDF/Cetak**.

Total: **137 test hijau** (131 feature + 6 unit).

## Antrian (Queue)

Notifikasi email dikirim melalui queue. Untuk local:

```sh
php artisan queue:work
```

Di produksi jalankan worker dengan supervisor/systemd, dan pastikan `MAIL_MAILER` diset ke SMTP. Tabel `jobs`/`failed_jobs` sudah tersedia.

## Ekspor laporan

Halaman laporan menyediakan 3 format: **Excel (XLSX)**, **PDF/Cetak** (buka dialog cetak → Simpan PDF), dan **CSV**.
XLSX dihasilkan tanpa dependensi eksternal (`app/Services/XlsxWriter.php`). Jika ingin PDF native, dapat ditambahkan `barryvdh/laravel-dompdf` saat Packagist terjangkau.

## Akun & akses audit

Audit log hanya dapat diakses role `super_admin`. HRD mengakses laporan tanpa audit log.

## Deployment production

1. `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` sesuai domain (HTTPS).
2. Aktifkan proteksi sesi: `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax`.
3. Konfigurasi email produksi (`MAIL_MAILER=smtp` + kredensial) agar reset password berfungsi (default `log` hanya untuk development).
4. Install & build:
   ```sh
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   npm ci && npm run build
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
5. Arahkan document root web server ke folder `public/`.
6. Pastikan registrasi publik **nonaktif** (memang sudah) — akun dibuat lewat menu Pengguna oleh Super Admin.

### Backup & pemulihan database

Backup harian (contoh, jalankan via cron/Task Scheduler):

```sh
mysqldump -u root -p HRDManagementSystem > backup/hris_$(date +%Y%m%d).sql
```

Restore:

```sh
mysql -u root -p HRDManagementSystem < backup/hris_YYYYMMDD.sql
```

Simpan berkas backup di luar `public/` (mis. `storage/backups`) dan lindungi aksesnya.

### Checklist keamanan

- [x] Registrasi publik dinonaktifkan.
- [x] Akun nonaktif tidak dapat login (dicek saat autentikasi + middleware).
- [x] Rate limiting: login, reset password, check-in/out, ekspor laporan.
- [x] Security headers (nosniff, SAMEORIGIN, Referrer-Policy, Permissions-Policy, HSTS di produksi).
- [x] CSRF protection (bawaan Laravel).
- [x] Authorization berlapis (route + policy + query scoping).
- [x] Audit log aksi sensitif (tanpa menyimpan password).
- [ ] HTTPS + backup otomatis (konfigurasi infrastruktur).
