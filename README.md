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
- **Fase 1B (Selesai):** Kebijakan saldo lanjutan — prorate karyawan baru, carry-over tahunan berbatas (`leave:carry-over` + scheduler), kedaluwarsa saldo; **multi-level approval** cuti (Manager → HRD untuk ≥ ambang hari, `config/leave.php`).
- **Fase 2 (Selesai):** Kualitas — lapisan konsistensi timezone (`config('app.display_timezone')` + helper `tz()/tz_now()/tz_today()/to_tz()`; seluruh kode/view memakai helper), factory lengkap semua model, konfigurasi PHPStan/Larastan + skrip `composer analyse`, CI menambah langkah static analysis (opsional).
- **Fase 3 (bagian 1 — Selesai):** Kebijakan password (min 12, huruf besar-kecil, angka; default global `Password::defaults()`), offboarding karyawan (resign + nonaktifkan akun + audit), pengingat kontrak (`contract_end_date` + command `contracts:remind` + scheduler harian), bulk activate/deactivate, arsip & restore soft-deleted, halaman **Struktur Organisasi**.
- **Fase 3 (bagian 2 — Selesai):** Roster/shift **mingguan** (`schedule_assignments`, jadwal efektif per tanggal dipakai absensi & cuti), **tukar shift** (pengajuan + approval manager/HRD yang menukar jadwal mingguan), **2FA OTP email** (kolom 2FA di users, `TwoFactorService`, notifikasi kode, halaman challenge, toggle di profil) — tanpa dependensi eksternal.
- **Fase 4 (Selesai):** REST API `/api/v1` dengan **token mandiri** (tabel `api_tokens`, `ApiTokenService`, middleware `auth.api`, rate limit 60/menit) — karena Sanctum tidak dapat dipasang; UI kelola token di Profil; endpoint me/attendance/leave/announcements. Operasional: endpoint `/health` (DB + queue), perintah `db:backup` (mysqldump + prune) terjadwal mingguan. Multi-cabang ditunda.
- **Fase 5 Rekrutmen/ATS (Selesai):** portal karir publik `/karir` (lamar tanpa login, CV privat, honeypot + rate limit), CRUD lowongan (draft/terbit/tutup), pipeline kanban, detail pelamar, penjadwalan & hasil wawancara, **konversi hired → karyawan** sekali klik, notifikasi email/in-app, audit `recruitment.*`.

Total: **207 test hijau** (201 feature + 6 unit).

## Portal Karir & Profil Perusahaan

- **Profil perusahaan** (`/settings/company`, HRD): nama, tagline, tentang, logo, kontak, sosial — tampil di halaman karir & structured data.
- **Halaman karir publik `/karir`**: hero perusahaan, pencarian + filter (departemen, mode kerja, tipe), kartu lowongan (badge mode/level/tipe/gaji, batas lamaran).
- **Job preview `/karir/{job}`**: badge, jumlah pelamar, batas lamaran, tombol **bagikan** (WhatsApp/LinkedIn/salin), section Tanggung Jawab/Kualifikasi/Benefit, "Lowongan Lain", status **ditutup** otomatis.
- **Lamar multi-step** (Data Diri → Berkas+tautan+ekspektasi gaji → Tinjau & Kirim) + **halaman status lamaran** via tautan bertoken (`/karir/lamaran/{token}`).
- **SEO**: meta OpenGraph + **JSON-LD `JobPosting`** (termasuk `TELECOMMUTE` untuk remote, `baseSalary`, `validThrough`) untuk Google for Jobs.
- Field lowongan baru: `work_mode`, `experience_level`, `responsibilities`, `benefits`, `closes_at`.
- Catatan: **AI screening CV sengaja tidak diimplementasikan** (perlu layanan AI berbayar).

## Rekrutmen (ATS)

- Publik: `/karir` (lowongan terbit) → lamar dengan CV (disimpan privat).
- Internal (HRD): **Lowongan**, **Pelamar** (daftar + filter), **Pipeline** (kanban), detail pelamar (ubah tahap, jadwalkan wawancara, catat hasil, konversi ke karyawan).
- Alur status: `applied → screening → interview → offer → hired` (+ `rejected`/`withdrawn`), divalidasi state machine.

## REST API

Autentikasi: header `Authorization: Bearer <token>`. Token dapat dibuat di **Profil → Token API**, atau lewat **login API**. Dokumentasi interaktif: **`/docs/api`** (OpenAPI: `/docs/api/openapi.json`).

**Login API**
```http
POST /api/v1/login
Content-Type: application/json

{"email":"karyawan@hris.test","password":"password","device_name":"Mobile"}
```
Respons (envelope seragam):
```json
{
  "success": true,
  "message": "Login berhasil.",
  "data": {
    "token": "<64 hex, tanpa |>",
    "token_type": "Bearer",
    "expires_at": "2026-12-05T...",
    "user": { "id": 4, "name": "Andi Wijaya", "email": "...", "roles": ["karyawan"], "permissions": ["..."] }
  }
}
```
Token berformat **64 karakter heksadesimal** (tanpa `|`). Header juga menerima format `id|token` ala Sanctum (bagian setelah `|` yang dipakai).

**Envelope**: semua endpoint mengembalikan `{ "success": bool, "message": string, "data": ..., "meta": {...}, "errors": {...} }`. Error validasi → `422` dengan `errors`; auth → `401`; forbidden → `403`.

| Method | Endpoint | Keterangan |
|---|---|---|
| POST | `/api/v1/login` | Login & terbitkan token (rate limit 5/menit) |
| GET | `/api/v1/me` | Profil user (roles + permissions) + data karyawan |
| GET | `/api/v1/attendance` | Riwayat absensi sendiri (paginated) |
| POST | `/api/v1/attendance/check-in` | Check-in |
| POST | `/api/v1/attendance/check-out` | Check-out |
| GET | `/api/v1/leave-balances` | Saldo cuti tahun berjalan |
| GET | `/api/v1/leaves` | Riwayat pengajuan cuti |
| POST | `/api/v1/leaves` | Ajukan cuti |
| GET | `/api/v1/announcements` | Pengumuman terbit |

Operasional: `GET /health` (status DB & queue), `php artisan db:backup` (backup ke `storage/app/backups`).

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

Atau lewat aplikasi (menyimpan ke `storage/app/backups`, prune otomatis):

```sh
php artisan db:backup
```

Restore (pilih berkas di `storage/app/backups` atau path absolut):

```sh
php artisan db:restore
php artisan db:restore hris_20260101_010000.sql --force
```

Simpan berkas backup di luar `public/` dan lindungi aksesnya.

### Queue worker & scheduler (production)

- Queue worker: contoh Supervisor di `deploy/supervisor-hris-worker.conf`.
- Scheduler: cron `* * * * * cd /path && php artisan schedule:run` atau systemd timer — lihat `deploy/scheduler.md`.

### Health check

`GET /health` mengembalikan JSON status `database`, `queue` (pending/failed), `disk` (free/total), dan `scheduler` (heartbeat 5 menit, dianggap `stale` bila > 15 menit). Status `503` bila ada komponen yang gagal.

### Coverage test (opsional)

```sh
php artisan test --coverage --min=50
```

Job coverage di CI bersifat opsional (`continue-on-error`).

### Checklist keamanan

- [x] Registrasi publik dinonaktifkan.
- [x] Akun nonaktif tidak dapat login (dicek saat autentikasi + middleware).
- [x] Rate limiting: login, reset password, check-in/out, ekspor laporan.
- [x] Security headers (nosniff, SAMEORIGIN, Referrer-Policy, Permissions-Policy, HSTS di produksi).
- [x] CSRF protection (bawaan Laravel).
- [x] Authorization berlapis (route + policy + query scoping).
- [x] Audit log aksi sensitif (tanpa menyimpan password).
- [ ] HTTPS + backup otomatis (konfigurasi infrastruktur).
