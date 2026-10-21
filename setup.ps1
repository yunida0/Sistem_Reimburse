#!/usr/bin/env pwsh
# =============================================================
# Reimbust — Setup Script
# Jalankan dari direktori d:\SEMESTER 3\RPL\REIMBUST
# =============================================================

$ErrorActionPreference = "Stop"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Definition
Set-Location $ScriptDir

Write-Host "=== Reimbust Setup ===" -ForegroundColor Cyan

# Jangan jalankan ulang jika proyek sudah ter-setup
if (Test-Path (Join-Path $ScriptDir "artisan")) {
    Write-Host "Proyek sudah ter-setup (artisan ditemukan)." -ForegroundColor Red
    Write-Host "Script ini hanya untuk setup awal di folder kosong." -ForegroundColor Red
    exit 1
}

# 1. Scaffold Laravel ke folder sementara agar kiro.md tidak tertimpa
Write-Host "`n[1/8] Scaffold Laravel 11..." -ForegroundColor Yellow
composer create-project laravel/laravel:^12.0 _laravel_temp --prefer-dist --no-interaction

# 2. Pindahkan isi _laravel_temp ke root (tanpa menimpa kiro.md)
Write-Host "`n[2/8] Memindahkan file Laravel ke direktori proyek..." -ForegroundColor Yellow
Get-ChildItem "_laravel_temp" -Force | ForEach-Object {
    $dest = Join-Path $ScriptDir $_.Name
    if ($_.Name -ne "kiro.md") {
        if (Test-Path $dest) {
            Remove-Item $dest -Recurse -Force
        }
        Move-Item $_.FullName $dest
    }
}
Remove-Item "_laravel_temp" -Recurse -Force

# 3. Install Breeze
Write-Host "`n[3/8] Install Laravel Breeze..." -ForegroundColor Yellow
composer require laravel/breeze --dev
php artisan breeze:install blade --no-interaction

# 4. Install dependensi tambahan
Write-Host "`n[4/8] Install dependensi PHP..." -ForegroundColor Yellow
composer require maatwebsite/excel:^3.1

# 5. Copy file kustom dari folder _custom ke tempat yang benar
Write-Host "`n[5/8] Copy file kustom proyek..." -ForegroundColor Yellow

# Fungsi helper copy dengan buat direktori otomatis
function CopyCustomFile($src, $dst) {
    $dstDir = Split-Path -Parent $dst
    if (-not (Test-Path $dstDir)) {
        New-Item -ItemType Directory -Path $dstDir -Force | Out-Null
    }
    Copy-Item $src $dst -Force
}

# Copy semua file dari _custom/
if (Test-Path "_custom") {
    Get-ChildItem "_custom" -Recurse -File | ForEach-Object {
        $rel = $_.FullName.Substring((Resolve-Path "_custom").Path.Length + 1)
        $dst = Join-Path $ScriptDir $rel
        CopyCustomFile $_.FullName $dst
    }
    Write-Host "  File kustom berhasil di-copy." -ForegroundColor Green
}

# 5b. Siapkan .env
Write-Host "`n[5b/8] Menyiapkan file .env..." -ForegroundColor Yellow
Copy-Item "_custom\.env.example" ".env" -Force

# 6. npm install + build
Write-Host "`n[6/8] Install Node dependencies & build assets..." -ForegroundColor Yellow
npm install
npm run build

# 7. Generate key + migrate + seed
Write-Host "`n[7/8] Setup database..." -ForegroundColor Yellow
php artisan key:generate
php artisan migrate:fresh --seed

# 8. Done
Write-Host "`n[8/8] Setup selesai!" -ForegroundColor Green
Write-Host ""
Write-Host "Jalankan server dengan:" -ForegroundColor Cyan
Write-Host "  php artisan serve" -ForegroundColor White
Write-Host ""
Write-Host "Akun demo:" -ForegroundColor Cyan
Write-Host "  admin@reimbust.test    / password  (Admin)" -ForegroundColor White
Write-Host "  budi@reimbust.test     / password  (Approver)" -ForegroundColor White
Write-Host "  rudi@reimbust.test     / password  (Employee)" -ForegroundColor White
Write-Host "  siti@reimbust.test     / password  (Employee)" -ForegroundColor White
Write-Host "  sari@reimbust.test     / password  (Finance)" -ForegroundColor White
Write-Host "  kasir@reimbust.test    / password  (Cashier)" -ForegroundColor White
