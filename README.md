# Reimbust — Sistem Reimbursement Internal

Aplikasi web reimbursement internal berbasis Laravel 12, Tailwind CSS, dan Alpine.js.

---

## Prasyarat

- PHP 8.2+ (XAMPP/Laragon) dengan ekstensi: `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `zip`
- Composer 2
- Node.js 20+ dan npm
- MySQL 8 / MariaDB via XAMPP atau Laragon
- phpMyAdmin

---

## Setup Awal (Manual)

### 1. Buat database

Buka phpMyAdmin, buat database baru:
- Nama: `reimbust`
- Collation: `utf8mb4_unicode_ci`

### 2. Scaffold Laravel

Jalankan dari terminal di direktori proyek (`d:\SEMESTER 3\RPL\REIMBUST`):

```powershell
# Scaffold Laravel ke folder sementara (agar kiro.md tidak tertimpa)
composer create-project laravel/laravel:^12.0 _laravel_temp --prefer-dist --no-interaction
```

Pindahkan isi `_laravel_temp` ke root (kecuali kiro.md):
```powershell
Get-ChildItem "_laravel_temp" -Force | ForEach-Object {
    if ($_.Name -ne "kiro.md") { Move-Item $_.FullName (Join-Path (Get-Location) $_.Name) -Force }
}
Remove-Item "_laravel_temp" -Recurse -Force
```

### 3. Copy file kustom

Salin semua file dari `_custom/` ke posisinya masing-masing:
```powershell
Get-ChildItem "_custom" -Recurse -File | ForEach-Object {
    $rel = $_.FullName.Substring((Resolve-Path "_custom").Path.Length + 1)
    $dst = Join-Path (Get-Location) $rel
    New-Item -ItemType Directory -Path (Split-Path $dst) -Force | Out-Null
    Copy-Item $_.FullName $dst -Force
}
```

### 4. Install dependensi

```powershell
composer install
composer require laravel/breeze --dev
php artisan breeze:install blade --no-interaction
composer require maatwebsite/excel:^3.1
npm install
```

### 5. Setup environment

```powershell
copy _custom\.env.example .env
php artisan key:generate
```

Edit `.env`:
```env
DB_DATABASE=reimbust
DB_USERNAME=root
DB_PASSWORD=
```

### 6. Migrate dan seed

```powershell
php artisan migrate:fresh --seed
```

### 7. Build assets

```powershell
npm run build
```

### 8. Jalankan server

**Opsi A — Apache XAMPP (sudah dikonfigurasi di mesin ini)**

1. Buka **XAMPP Control Panel**.
2. Klik **Start** pada **Apache** dan **MySQL**.
3. Buka: **http://localhost/reimbust**

Konfigurasi Apache ada di `C:\xampp\apache\conf\extra\httpd-reimbust.conf`
(di-include dari `C:\xampp\apache\conf\httpd.conf`). File ini mengarahkan
`/reimbust` ke `D:\SEMESTER 3\RPL\REIMBUST\public`.

**Opsi B — server bawaan Laravel**

```powershell
php artisan serve
```

Buka: http://127.0.0.1:8000

---

## Akun Demo

Semua password: `password`

| Email | Nama | Peran |
|---|---|---|
| admin@reimbust.test | Admin Reimbust | Admin |
| budi@reimbust.test | Budi Santoso | Atasan (Approver) |
| rudi@reimbust.test | Rudi Hartono | Pegawai |
| siti@reimbust.test | Siti Aminah | Pegawai |
| sari@reimbust.test | Sari Wulandari | Finance |
| kasir@reimbust.test | Andi Pratama | Kasir |

---

## Alur Demo

1. Login sebagai `rudi@reimbust.test`
2. Buat pengajuan "Perjalanan Dinas Yogyakarta" → 3 item → unggah bukti → Simpan & Ajukan
3. Login sebagai `budi@reimbust.test` → Persetujuan → Periksa → Setujui
4. Login sebagai `sari@reimbust.test` → Verifikasi Finance → centang 3 checklist → Verifikasi
5. Login sebagai `kasir@reimbust.test` → Pembayaran → isi form → **unggah bukti transfer** → Konfirmasi
6. Login sebagai `rudi@reimbust.test` → buka pengajuan → lihat status Dibayar → unduh bukti pembayaran
7. Login sebagai `sari@reimbust.test` → Laporan → filter bulan ini → Export Excel

---

## Struktur Direktori Penting

```
app/
├── Enums/           # UserRole, ReimbursementStatus, dll.
├── Http/
│   ├── Controllers/ # Semua controller
│   ├── Middleware/  # EnsureUserHasRole
│   └── Requests/    # Form Requests
├── Models/          # Eloquent models
├── Notifications/   # ReimbursementNotification
├── Policies/        # ReimbursementPolicy
├── Services/        # WorkflowService, ReimbursementService, dll.
└── Exports/         # ReimbursementsExport

database/
├── migrations/      # Semua migration
└── seeders/         # DatabaseSeeder + seeder per tabel

resources/
├── css/             # Tailwind CSS
├── js/              # Alpine.js
└── views/           # Blade templates

config/
└── reimbust.php     # Konfigurasi aplikasi
```

---

## Catatan Teknis

- File lampiran disimpan di `storage/app/private/` (bukan public)
- Akses file hanya melalui route `/attachments/{id}` setelah cek policy
- Semua transisi status melalui `WorkflowService` dengan `DB::transaction()`
- Kasir **wajib** mengunggah bukti pembayaran — tanpa bukti, status tidak berubah
