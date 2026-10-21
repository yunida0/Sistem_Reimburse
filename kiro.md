# Reimbust — Brief Vibecode

**Nama aplikasi:** Reimbust
**Jenis:** Aplikasi web internal sistem reimbursement
**Stack:** Laravel + MySQL (phpMyAdmin) + Blade + Tailwind CSS + Alpine.js
**Bahasa UI:** Indonesia
**OCR/ICR:** tidak digunakan

> Dokumen ini adalah instruksi utama untuk vibecode. Baca seluruh dokumen sebelum menulis kode. Bangun aplikasi secara bertahap mengikuti urutan implementasi. Jangan menambahkan fitur di luar cakupan tanpa diminta.

---

## 0. Keputusan final

1. Database menggunakan **MySQL**, dikelola lewat **phpMyAdmin** (XAMPP/Laragon).
2. Framework **Laravel** (versi stabil terbaru, minimal Laravel 12) dengan **PHP 8.2+**.
3. Frontend memakai **Blade + Tailwind CSS + Alpine.js**. Tidak memakai React/Vue/Inertia/Livewire.
4. Autentikasi memakai **Laravel Breeze (Blade stack)**, hanya login. Tidak ada registrasi publik.
5. **Tidak ada OCR/ICR.** Semua nominal, tanggal, dan data struk diisi manual.
6. Pengajuan = satu perjalanan/kegiatan, berisi banyak item biaya.
7. Alur: **Pegawai → Atasan → Finance → Kasir → Dibayar**.
8. **Kasir wajib mengunggah bukti pembayaran/bukti transfer saat menandai pengajuan sebagai dibayar.** Tanpa bukti, aksi pembayaran ditolak.
9. Bukti pembayaran dapat dilihat dan diunduh oleh pegawai pemilik pengajuan.
10. Semua nominal disimpan sebagai integer rupiah. Tidak ada desimal.

---

## 1. Ringkasan produk

Reimbust menggantikan alur reimbursement berbasis kertas dan WhatsApp dengan proses digital:

- Pegawai membuat pengajuan, mengisi item biaya, dan mengunggah bukti transaksi.
- Atasan memeriksa dan menyetujui, meminta revisi, atau menolak.
- Finance memverifikasi kelengkapan dan kesesuaian.
- Kasir membayar dan **wajib mengunggah bukti transfer/bukti pembayaran**.
- Semua pihak dapat melihat status, riwayat, komentar, dan notifikasi.
- Finance dan manajemen mendapat laporan otomatis serta export CSV/Excel.

Aplikasi harus nyaman dipakai di desktop dan HP, terutama untuk upload foto struk dari kamera HP.

---

## 2. Stack & dependensi

| Kebutuhan | Pilihan |
|---|---|
| Framework | Laravel 12+ |
| Bahasa | PHP 8.2+ |
| Database | MySQL 8 / MariaDB (phpMyAdmin) |
| Auth | Laravel Breeze (Blade) |
| CSS | Tailwind CSS |
| Interaksi | Alpine.js |
| Build | Vite |
| Export Excel | `maatwebsite/excel` |
| Export CSV | `maatwebsite/excel` (CSV writer) |
| Grafik | Chart.js |
| Testing | PHPUnit (default Laravel) |

Jangan memakai Docker. Aplikasi harus bisa dijalankan dengan XAMPP/Laragon di Windows.

---

## 3. Peran & hak akses

### 3.1 Daftar peran

| Peran | Enum | Deskripsi |
|---|---|---|
| Pegawai | `employee` | Membuat dan memantau pengajuannya sendiri |
| Atasan | `approver` | Menyetujui pengajuan bawahan langsung |
| Finance | `finance` | Memverifikasi pengajuan yang sudah disetujui atasan |
| Kasir | `cashier` | Membayar pengajuan yang sudah diverifikasi dan mengunggah bukti |
| Admin | `admin` | Mengelola master data dan melihat semua data |

Semua peran tetap boleh membuat pengajuan reimbursement untuk dirinya sendiri.

### 3.2 Aturan akses data

| Aksi | Pegawai | Atasan | Finance | Kasir | Admin |
|---|---|---|---|---|---|
| Lihat pengajuan sendiri | Ya | Ya | Ya | Ya | Ya |
| Lihat pengajuan bawahan langsung | - | Ya | Ya | - | Ya |
| Lihat semua pengajuan | - | - | Ya | Hanya `VERIFIED`/`PAID` | Ya |
| Buat pengajuan | Ya | Ya | Ya | Ya | Ya |
| Edit draf/revisi milik sendiri | Ya | Ya | Ya | Ya | Ya |
| Setujui / revisi / tolak (tahap atasan) | - | Ya | - | - | - |
| Verifikasi / revisi / tolak (tahap finance) | - | - | Ya | - | - |
| Tandai dibayar + upload bukti | - | - | - | Ya | - |
| Lihat & export laporan | - | - | Ya | - | Ya |
| Kelola user/departemen/kategori | - | - | - | - | Ya |

Catatan:
- Akses approval ditentukan oleh relasi `users.manager_id`, bukan hanya role. Role `approver` adalah label.
- Atasan tidak boleh menyetujui pengajuannya sendiri.
- Admin tidak boleh melakukan aksi verifikasi atau pembayaran.
- Menu "Persetujuan" hanya muncul jika user memiliki bawahan langsung.

### 3.3 Policy

Buat `ReimbursementPolicy` dengan method:

- `view`: owner, manager dari owner, finance, admin, atau kasir jika status `VERIFIED`/`PAID`.
- `update`: owner dan status `DRAFT`/`REVISION`.
- `submit`: owner dan status `DRAFT`/`REVISION`.
- `cancel`: owner dan status `DRAFT`/`SUBMITTED`/`REVISION`.
- `comment`: siapa pun yang boleh `view`.
- `approve`, `returnByApprover`, `rejectByApprover`: user adalah `manager_id` dari owner, status `SUBMITTED`, dan bukan owner.
- `verify`, `returnByFinance`, `rejectByFinance`: role `finance`, status `APPROVED`.
- `pay`: role `cashier`, status `VERIFIED`.
- `downloadAttachment`: siapa pun yang boleh `view` pengajuan induknya.

Semua pengecekan dilakukan di server. Jangan hanya menyembunyikan tombol di UI.

---

## 4. Status & transisi

### 4.1 Enum status

| Enum | Label UI | Warna badge |
|---|---|---|
| `DRAFT` | Draf | Slate |
| `SUBMITTED` | Menunggu Atasan | Biru |
| `REVISION` | Perlu Revisi | Amber |
| `APPROVED` | Menunggu Finance | Indigo |
| `VERIFIED` | Menunggu Pembayaran | Cyan |
| `PAID` | Dibayar | Hijau |
| `REJECTED` | Ditolak | Merah |
| `CANCELLED` | Dibatalkan | Abu-abu |

Status final: `PAID`, `REJECTED`, `CANCELLED`.

### 4.2 Transisi

| Aksi | Dari | Ke | Pelaku | Catatan |
|---|---|---|---|---|
| Submit | `DRAFT` | `SUBMITTED` | Owner | Validasi kelengkapan |
| Resubmit (revisi atasan) | `REVISION` + `revision_from=approver` | `SUBMITTED` | Owner | Kembali ke atasan |
| Resubmit (revisi finance) | `REVISION` + `revision_from=finance` | `APPROVED` | Owner | Persetujuan atasan tetap berlaku |
| Approve | `SUBMITTED` | `APPROVED` | Atasan | - |
| Return atasan | `SUBMITTED` | `REVISION` + `revision_from=approver` | Atasan | Catatan wajib |
| Reject atasan | `SUBMITTED` | `REJECTED` | Atasan | Catatan wajib |
| Verify | `APPROVED` | `VERIFIED` | Finance | Checklist wajib |
| Return finance | `APPROVED` | `REVISION` + `revision_from=finance` | Finance | Catatan wajib |
| Reject finance | `APPROVED` | `REJECTED` | Finance | Catatan wajib |
| Pay | `VERIFIED` | `PAID` | Kasir | **Bukti pembayaran wajib** |
| Cancel | `DRAFT`/`SUBMITTED`/`REVISION` | `CANCELLED` | Owner | Catatan opsional |

Aturan tambahan:
- Transisi hanya boleh lewat service, bukan update kolom langsung dari controller.
- Gunakan `DB::transaction()` dan `lockForUpdate()` saat mengubah status.
- Setiap transisi menulis `activity_logs` dan mengirim notifikasi.
- Saat return dari atasan: kosongkan `approved_at`.
- Saat return dari finance: kosongkan `verified_at`, pertahankan `approved_at`.
- Saat resubmit: perbarui `submitted_at`, lalu kosongkan `revision_from`.
- Setelah `PAID`, data tidak boleh diubah lagi.

---

## 5. Skema database

Gunakan migration Laravel. Nama tabel dan kolom dalam bahasa Inggris.

### 5.1 `users`

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| name | string | |
| email | string unique | |
| password | string | bcrypt |
| role | enum `employee,approver,finance,cashier,admin` | default `employee` |
| department_id | FK `departments` nullable | |
| manager_id | FK `users` nullable | atasan langsung |
| is_active | boolean default true | |
| remember_token | string nullable | |
| timestamps | | |

### 5.2 `departments`

| Kolom | Tipe |
|---|---|
| id | bigint PK |
| name | string |
| code | string unique |
| is_active | boolean default true |
| timestamps | |

### 5.3 `categories`

| Kolom | Tipe |
|---|---|
| id | bigint PK |
| name | string |
| code | string unique |
| requires_receipt | boolean default true |
| is_active | boolean default true |
| timestamps | |

### 5.4 `reimbursements`

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| code | string unique nullable | contoh `RB-2026-0001` |
| user_id | FK `users` | pemilik |
| department_id | FK `departments` | snapshot departemen saat dibuat |
| title | string(150) | |
| description | text nullable | |
| total_amount | unsignedBigInteger default 0 | integer rupiah |
| status | enum status | default `DRAFT` |
| revision_from | enum `approver,finance` nullable | |
| submitted_at | timestamp nullable | |
| approved_at | timestamp nullable | |
| verified_at | timestamp nullable | |
| paid_at | timestamp nullable | |
| payment_method | enum `transfer,cash` nullable | |
| payment_reference | string nullable | |
| payment_note | text nullable | |
| timestamps | | |

Index: `status`, `user_id`, `department_id`, `submitted_at`.

### 5.5 `reimbursement_items`

| Kolom | Tipe |
|---|---|
| id | bigint PK |
| reimbursement_id | FK cascade |
| category_id | FK restrict |
| expense_date | date |
| description | string |
| vendor | string nullable |
| amount | unsignedBigInteger |
| sort_order | unsignedInteger default 0 |
| timestamps | |

### 5.6 `attachments`

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| reimbursement_id | FK cascade | |
| item_id | FK `reimbursement_items` nullable cascade | null untuk bukti pembayaran |
| kind | enum `receipt,payment_proof` | |
| original_name | string | nama asli file |
| stored_path | string | path private |
| mime_type | string | |
| size_bytes | unsignedBigInteger | |
| uploaded_by | FK `users` | |
| timestamps | | |

### 5.7 `activity_logs`

| Kolom | Tipe |
|---|---|
| id | bigint PK |
| reimbursement_id | FK cascade |
| actor_id | FK `users` nullable nullOnDelete |
| action | string |
| from_status | string nullable |
| to_status | string nullable |
| note | text nullable |
| meta | json nullable |
| created_at | timestamp |

Tidak ada `updated_at`. Log tidak boleh diedit atau dihapus.

### 5.8 `comments`

| Kolom | Tipe |
|---|---|
| id | bigint PK |
| reimbursement_id | FK cascade |
| user_id | FK `users` |
| body | text |
| timestamps | |

### 5.9 `notifications`

Gunakan tabel notifikasi bawaan Laravel (`php artisan notifications:table`).

### 5.10 Enum PHP

Buat enum:

- `App\Enums\UserRole`
- `App\Enums\ReimbursementStatus`
- `App\Enums\RevisionFrom`
- `App\Enums\AttachmentKind`
- `App\Enums\PaymentMethod`

Setiap enum punya `label(): string`. `ReimbursementStatus` juga punya `color(): string` dan `isFinal(): bool`.

### 5.11 Model & relasi

- `User`: `department()`, `manager()`, `subordinates()`, `reimbursements()`, `notifications()`.
- `Department`: `users()`, `reimbursements()`.
- `Category`: `items()`.
- `Reimbursement`: `user()`, `department()`, `items()`, `attachments()`, `logs()`, `comments()`, `paymentProofs()`.
- `ReimbursementItem`: `reimbursement()`, `category()`, `attachments()`.
- `Attachment`: `reimbursement()`, `item()`, `uploader()`.
- `ActivityLog`: `reimbursement()`, `actor()`.
- `Comment`: `reimbursement()`, `user()`.

Gunakan cast enum pada model.

---

## 6. Aturan bisnis & validasi

### 6.1 Umum

1. Nominal disimpan sebagai integer rupiah, minimal 1, maksimal 999.999.999.
2. Total pengajuan selalu dihitung ulang di server: `sum(items.amount)`.
3. Minimal 1 item saat submit, maksimal 50 item.
4. `expense_date` tidak boleh melebihi hari ini.
5. Pengajuan hanya bisa diedit saat `DRAFT` atau `REVISION`.
6. Setelah submit, item dan lampiran tidak bisa dihapus, kecuali lewat revisi.
7. Lampiran bersifat append-only setelah submit. File lama tetap tersimpan saat revisi.
8. Revisi dan penolakan wajib menyertakan catatan (1–1000 karakter).
9. Komentar 1–1000 karakter, teks biasa (escape HTML).
10. Atasan tidak boleh memproses pengajuannya sendiri.
11. Admin tidak boleh memverifikasi atau membayar.
12. Zona waktu `Asia/Jakarta`, locale `id`.
13. Format tanggal `d M Y`, nominal `Rp1.234.567`.
14. Kode pengajuan: `RB-{TAHUN}-{ID 4 digit}`, contoh `RB-2026-0001`. Dibuat setelah insert dalam transaksi.
15. Semua perubahan status wajib melalui `WorkflowService`.

### 6.2 Draf

- `title` wajib, maksimal 150 karakter.
- `description` opsional, maksimal 2000 karakter.
- Item opsional. Baris yang terisi sebagian harus divalidasi; baris kosong diabaikan.
- Bukti transaksi opsional saat draf.

### 6.3 Submit

- Minimal 1 item lengkap.
- Setiap item wajib punya `expense_date`, `category_id`, `description`, dan `amount`.
- Jika `categories.requires_receipt = true`, item wajib punya minimal 1 lampiran bukti (lama atau baru).
- Maksimal 5 file per item.
- Jika validasi gagal saat "Simpan & Ajukan", draf tetap tersimpan dan error ditampilkan.

### 6.4 File

- Format: JPG, JPEG, PNG, WEBP, PDF.
- Maksimal 5 MB per file.
- Validasi MIME dan ekstensi.
- Simpan di disk `local` (private), bukan `public`.
- Path bukti transaksi: `receipts/{reimbursement_id}/{random}.{ext}`.
- Path bukti pembayaran: `payments/{reimbursement_id}/{random}.{ext}`.
- Akses file hanya lewat route ber-autentikasi dan ber-policy.
- Jangan pernah memakai nama file asli sebagai path.

### 6.5 Approval

- Hanya atasan langsung (`manager_id`) yang boleh approve/return/reject.
- Hanya saat status `SUBMITTED`.
- Approve tidak butuh catatan.
- Return/reject wajib catatan.

### 6.6 Finance

- Hanya role `finance`.
- Hanya saat status `APPROVED`.
- Sebelum verify, wajib mencentang 3 checklist:
  1. Bukti transaksi lengkap.
  2. Nominal sesuai.
  3. Tanggal sesuai.
- Return/reject wajib catatan.

### 6.7 Pembayaran (Kasir) — WAJIB BUKTI

- Hanya role `cashier`.
- Hanya saat status `VERIFIED`.
- Field wajib:
  - `paid_at` (tanggal pembayaran, tidak boleh masa depan, default hari ini).
  - `payment_method` (`transfer` atau `cash`).
  - `payment_reference` wajib jika metode `transfer`.
  - `proof` file bukti pembayaran/bukti transfer **wajib**, format JPG/PNG/WEBP/PDF, maksimal 5 MB.
  - `payment_note` opsional, maksimal 1000 karakter.
- Pembayaran penuh. Tidak ada pembayaran sebagian.
- Simpan attachment `kind=payment_proof`, `item_id=null`, `uploaded_by=kasir`.
- Setelah sukses: status `PAID`, `paid_at` diisi, activity log, notifikasi ke pemilik.
- Bukti pembayaran tampil di halaman detail pengajuan dan dapat diunduh pemilik.
- Jika upload bukti gagal, jangan ubah status.

### 6.8 Pembatalan

- Owner dapat membatalkan `DRAFT`, `SUBMITTED`, atau `REVISION`.
- Jika pengajuan sedang `SUBMITTED`, atasan mendapat notifikasi.
- Pengajuan `APPROVED` ke atas tidak bisa dibatalkan oleh pegawai.

### 6.9 Aging

- Hitung umur pengajuan di status sekarang dari `updated_at`.
- Tampilkan badge hanya untuk `SUBMITTED`, `APPROVED`, `VERIFIED`, dan `REVISION`.
- Ambang dari `config/reimbust.php`:
  - `aging_warning_days` = 3 (kuning).
  - `aging_danger_days` = 5 (merah).
- Tampilkan teks "X hari" di antrean dan detail.

---

## 7. Route & controller

Gunakan named routes dan route model binding.

### 7.1 Auth

| Method | URI | Keterangan |
|---|---|---|
| GET | `/login` | Halaman login |
| POST | `/login` | Proses login |
| POST | `/logout` | Logout |

Hapus route registrasi. Jangan aktifkan verifikasi email.

### 7.2 Dashboard & profil

| Method | URI | Controller |
|---|---|---|
| GET | `/dashboard` | `DashboardController@index` |
| GET/PATCH | `/profile` | Breeze profile |
| PUT | `/password` | Breeze password |

### 7.3 Pengajuan

| Method | URI | Controller / aksi |
|---|---|---|
| GET | `/reimbursements` | `ReimbursementController@index` |
| GET | `/reimbursements/create` | `@create` |
| POST | `/reimbursements` | `@store` |
| GET | `/reimbursements/{reimbursement}` | `@show` |
| GET | `/reimbursements/{reimbursement}/edit` | `@edit` |
| PUT | `/reimbursements/{reimbursement}` | `@update` |
| POST | `/reimbursements/{reimbursement}/submit` | `@submit` |
| POST | `/reimbursements/{reimbursement}/cancel` | `@cancel` |
| POST | `/reimbursements/{reimbursement}/comments` | `CommentController@store` |
| GET | `/attachments/{attachment}` | `AttachmentController@show` |
| DELETE | `/attachments/{attachment}` | `AttachmentController@destroy` |

### 7.4 Approval

| Method | URI | Controller |
|---|---|---|
| GET | `/approvals` | `ApprovalController@index` |
| POST | `/approvals/{reimbursement}/approve` | `@approve` |
| POST | `/approvals/{reimbursement}/return` | `@return` |
| POST | `/approvals/{reimbursement}/reject` | `@reject` |

### 7.5 Finance

| Method | URI | Controller |
|---|---|---|
| GET | `/finance/verifications` | `FinanceController@index` |
| POST | `/finance/{reimbursement}/verify` | `@verify` |
| POST | `/finance/{reimbursement}/return` | `@return` |
| POST | `/finance/{reimbursement}/reject` | `@reject` |

### 7.6 Pembayaran

| Method | URI | Controller |
|---|---|---|
| GET | `/payments` | `PaymentController@index` |
| GET | `/payments/{reimbursement}/pay` | `@create` |
| POST | `/payments/{reimbursement}/pay` | `@store` |

### 7.7 Notifikasi

| Method | URI | Controller |
|---|---|---|
| GET | `/notifications` | `NotificationController@index` |
| POST | `/notifications/{notification}/read` | `@markAsRead` |
| POST | `/notifications/read-all` | `@markAllAsRead` |

### 7.8 Laporan

| Method | URI | Controller |
|---|---|---|
| GET | `/reports` | `ReportController@index` |
| GET | `/reports/export` | `ReportController@export` |

### 7.9 Admin

| Method | URI | Controller |
|---|---|---|
| GET/POST/GET/PUT | `/admin/users...` | `Admin\UserController` |
| GET/POST/GET/PUT | `/admin/departments...` | `Admin\DepartmentController` |
| GET/POST/GET/PUT | `/admin/categories...` | `Admin\CategoryController` |

Tidak ada route delete untuk master data. Gunakan toggle `is_active`.

### 7.10 Middleware

Buat middleware `EnsureUserHasRole` dengan alias `role`, menerima beberapa role:

```php
Route::middleware(['auth', 'role:finance'])->group(...);
Route::middleware(['auth', 'role:finance,admin'])->group(...);
Route::middleware(['auth', 'role:cashier'])->group(...);
Route::middleware(['auth', 'role:admin'])->group(...);
```

Approval tidak memakai middleware role; otorisasi lewat policy dan pengecekan bawahan langsung.

---

## 8. Halaman & UI

### 8.1 Gaya visual

- Nama di sidebar: **Reimbust**.
- Sidebar gelap `slate-900`, item aktif `blue-600`, konten `slate-50`.
- Card putih, `rounded-xl`, `border slate-200`, `shadow-sm`.
- Font: Inter (Google Fonts) atau font sans bawaan.
- Primary: `blue-600`; success `emerald-600`; warning `amber-500`; danger `red-600`.
- Bahasa Indonesia untuk semua label, pesan validasi, dan empty state.
- Responsive: sidebar menjadi slide-over di mobile; tabel bisa di-scroll horizontal.
- Tidak ada dark mode di v1.
- Gunakan Blade components untuk badge status, badge aging, format uang, empty state, page header, modal konfirmasi, dan preview lampiran.

### 8.2 Layout

- Sidebar:
  - Dashboard
  - Pengajuan
  - Persetujuan (jika punya bawahan)
  - Verifikasi Finance (finance)
  - Pembayaran (cashier)
  - Laporan (finance/admin)
  - Master Data (admin)
  - Notifikasi (dengan badge unread)
  - Profil
- Topbar: judul halaman, lonceng notifikasi, menu user (profil, logout).
- Flash message sukses/error di atas konten.

### 8.3 Login

- Card terpusat, logo/nama Reimbust, tagline "Sistem Reimbursement Internal".
- Email, password, tombol "Masuk".
- Tidak ada link registrasi.
- Tampilkan akun demo di halaman login hanya jika `APP_ENV=local`.

### 8.4 Dashboard

Satu halaman role-aware.

**Semua user:**
- Sapaan "Selamat datang, {nama}".
- Kartu ringkasan pengajuan sendiri: Draf, Menunggu Atasan, Perlu Revisi, total dibayar bulan ini.
- Tabel 5 pengajuan terbaru + tombol "Buat Pengajuan".

**Atasan (jika punya bawahan):**
- Kartu "Menunggu Persetujuan".
- Daftar 5 pengajuan tertua dengan badge aging.
- Total tim dibayar bulan ini.

**Finance:**
- Kartu "Menunggu Verifikasi", "Menunggu Pembayaran", "Dibayar Bulan Ini".
- Daftar antrean tertua.

**Kasir:**
- Kartu "Siap Dibayar", "Dibayar Hari Ini".
- Daftar antrean pembayaran.

**Admin:**
- Total pengajuan perusahaan, total dibayar bulan ini, jumlah user aktif.
- Grafik tren 6 bulan.

### 8.5 Daftar pengajuan

- Filter: status, pencarian kode/judul/nama pegawai, rentang tanggal submit.
- Tabel desktop: Kode, Pegawai, Judul, Tanggal Submit, Total, Status, Aging, Aksi.
- Mobile: card list.
- Urut terbaru.
- Pagination 10/20/50.
- Scope data mengikuti policy.
- Tombol "Buat Pengajuan" untuk semua user.
- Employee hanya melihat miliknya; atasan melihat miliknya + bawahan; finance/admin semua; kasir hanya `VERIFIED`/`PAID` + miliknya.

### 8.6 Form pengajuan

Halaman `create` dan `edit`.

- Jika mode revisi, tampilkan alert berisi catatan revisi dan siapa yang meminta.
- Section "Informasi Pengajuan": judul, deskripsi.
- Section "Rincian Biaya": daftar item dinamis dengan Alpine.js.
- Setiap item berisi:
  - Tanggal pengeluaran (`type=date`, max hari ini).
  - Kategori (select kategori aktif).
  - Deskripsi.
  - Vendor (opsional).
  - Nominal (number, min 1, step 1, prefix "Rp").
  - Upload bukti (multiple, JPG/PNG/WEBP/PDF, maks 5 MB/file).
  - Daftar lampiran lama (jika edit) dengan tombol hapus dan preview.
  - Tombol hapus item.
- Tombol "Tambah Item".
- Ringkasan sticky: jumlah item, total, tombol:
  - "Simpan Draf" (secondary).
  - "Simpan & Ajukan" (primary).
- Submit form mengirim `action=draft` atau `action=submit`.
- Total di UI hanya preview. Server tetap menghitung ulang.
- Tampilkan error summary di atas form dan error per field.
- Gunakan `old()` agar input tidak hilang saat validasi gagal.
- Panduan singkat: "Pastikan tanggal dan nominal pada struk terlihat jelas."

### 8.7 Detail pengajuan

- Header: kode, judul, badge status, badge aging, total.
- Meta: pegawai, departemen, tanggal dibuat, tanggal submit.
- Tombol aksi sesuai peran dan status:
  - Owner: Edit, Ajukan, Batalkan.
  - Atasan: Setujui, Minta Revisi, Tolak.
  - Finance: Verifikasi, Minta Revisi, Tolak.
  - Kasir: Tandai Dibayar.
- Alert revisi jika status `REVISION`.
- Tabel item: tanggal, kategori, deskripsi, vendor, nominal, bukti (thumbnail/PDF).
- Klik bukti membuka modal preview; gambar bisa zoom, PDF tampil di iframe.
- Kartu pembayaran jika `PAID`: tanggal, metode, referensi, catatan, dan **bukti pembayaran yang bisa diunduh**.
- Timeline aktivitas (terlama → terbaru) dengan aktor, aksi, waktu, catatan.
- Section komentar: daftar komentar + form kirim.
- Modal konfirmasi untuk approve/pay.
- Modal dengan textarea wajib untuk return/reject.
- Modal verifikasi finance dengan 3 checkbox wajib.

### 8.8 Antrean approval

- Hanya pengajuan bawahan langsung.
- Hanya status `SUBMITTED`.
- Urut tertua.
- Kolom: Kode, Pegawai, Judul, Tanggal Submit, Total, Aging, Aksi "Periksa".
- Empty state "Tidak ada pengajuan yang menunggu persetujuan."

### 8.9 Antrean finance

- Hanya status `APPROVED`.
- Urut tertua.
- Kolom serupa + tombol "Verifikasi".

### 8.10 Pembayaran

**Daftar:**
- Hanya status `VERIFIED`.
- Urut tertua.
- Kolom: Kode, Pegawai, Tanggal Verifikasi, Total, Aging, Aksi "Bayar".

**Form pembayaran:**
- Ringkasan pengajuan + total.
- Tanggal pembayaran (default hari ini, max hari ini).
- Metode: Transfer / Tunai.
- Nomor referensi (wajib jika transfer).
- Catatan (opsional).
- **Upload bukti pembayaran/bukti transfer (wajib).**
- Preview nama file sebelum upload.
- Tombol "Konfirmasi Pembayaran" dengan modal konfirmasi.
- Setelah sukses redirect ke detail dengan flash "Pembayaran berhasil dicatat."

### 8.11 Laporan

Akses: finance dan admin.

Filter:
- Rentang tanggal submit (default bulan berjalan).
- Departemen.
- Status.

Kartu ringkasan:
- Jumlah pengajuan.
- Total nominal.
- Total dibayar.
- Rata-rata lama proses (hari) dari submit sampai bayar.

Grafik:
- Tren 6 bulan: total diajukan vs total dibayar (Chart.js).
- Komposisi kategori (doughnut).

Tabel:
- Rekap pengajuan: kode, pegawai, departemen, judul, tanggal submit, total, status, tanggal bayar.
- Pagination 20.

Rekap tambahan:
- Per departemen: jumlah, total, dibayar.
- Per kategori: jumlah item, total. Bisa difilter kategori.

Export:
- Tombol "Export Excel" dan "Export CSV".
- Mengikuti filter aktif.
- Kolom: Kode, Pegawai, Departemen, Judul, Tanggal Submit, Total, Status, Tanggal Bayar, Metode, Referensi.
- Draft tidak ikut diexport.

### 8.12 Notifikasi

- Halaman daftar notifikasi, terbaru.
- Belum dibaca: background biru muda + titik.
- Klik notifikasi menandai dibaca dan membuka pengajuan terkait.
- Tombol "Tandai semua dibaca".
- Badge unread di sidebar/topbar.

### 8.13 Admin

**User:**
- Tabel: nama, email, role, departemen, atasan, status.
- Form create/edit.
- Password wajib saat create, opsional saat edit.
- Tidak bisa menonaktifkan akun sendiri.
- Tidak ada delete.

**Departemen:**
- Tabel + form create/edit.
- Kode unik.
- Toggle aktif.

**Kategori:**
- Tabel + form create/edit.
- Kode unik.
- Checkbox "Wajib bukti".
- Toggle aktif.

### 8.14 Empty state & error

- Setiap tabel punya empty state dengan ikon, teks, dan CTA bila relevan.
- 403 memakai halaman error yang ramah.
- 404 memakai halaman bawaan Laravel yang disesuaikan.
- Jangan tampilkan stack trace di production.

---

## 9. Service & komponen backend

Buat service berikut:

### 9.1 `ReimbursementService`

- `createDraft(User $user, array $data): Reimbursement`
- `updateDraft(Reimbursement $r, array $data): void`
- `syncItems(Reimbursement $r, array $items): void`
- `storeUploadedReceipts(Reimbursement $r, array $items, User $actor): void`
- `recalculateTotal(Reimbursement $r): void`
- `assertReadyToSubmit(Reimbursement $r): void`
- `generateCode(Reimbursement $r): void`

### 9.2 `WorkflowService`

- `submit(Reimbursement $r, User $actor): void`
- `approve(Reimbursement $r, User $actor): void`
- `returnByApprover(Reimbursement $r, User $actor, string $note): void`
- `rejectByApprover(Reimbursement $r, User $actor, string $note): void`
- `verify(Reimbursement $r, User $actor, array $checklist): void`
- `returnByFinance(Reimbursement $r, User $actor, string $note): void`
- `rejectByFinance(Reimbursement $r, User $actor, string $note): void`
- `pay(Reimbursement $r, User $actor, array $data, UploadedFile $proof): void`
- `cancel(Reimbursement $r, User $actor, ?string $note): void`

Semua method memakai transaksi dan menulis activity log + notifikasi.

### 9.3 `ActivityLogger`

- `log(Reimbursement $r, ?User $actor, string $action, ?string $from, ?string $to, ?string $note = null, array $meta = [])`

### 9.4 `NotificationService`

- Kirim notifikasi database ke pemilik, atasan, finance, atau kasir sesuai transisi.
- Jangan mengirim notifikasi ke pelaku aksi sendiri.

### 9.5 Form Request

Buat Form Request terpisah:

- `StoreReimbursementRequest`
- `UpdateReimbursementRequest`
- `SubmitReimbursementRequest`
- `ApprovalActionRequest`
- `FinanceActionRequest`
- `PaymentRequest`
- `CommentRequest`
- `StoreUserRequest`
- `UpdateUserRequest`
- `DepartmentRequest`
- `CategoryRequest`

Semua pesan validasi dalam bahasa Indonesia.

### 9.6 Helper uang

Buat Blade component `<x-money :amount="$amount" />` yang menghasilkan `Rp1.234.567`.

---

## 10. Notifikasi

Gunakan notifikasi database Laravel.

Buat `App\Notifications\ReimbursementNotification` dengan data:
- `title`
- `message`
- `url`
- `level` (`info`, `success`, `warning`, `danger`)

Penerima:

| Kejadian | Penerima |
|---|---|
| Submit / resubmit ke atasan | Atasan |
| Resubmit revisi finance | Semua user finance aktif |
| Approve | Pemilik + semua finance aktif |
| Return / reject | Pemilik |
| Verify | Pemilik + semua kasir aktif |
| Pay | Pemilik |
| Komentar | Pemilik atau pemroses saat ini (bukan pengirim komentar) |
| Cancel saat submitted | Atasan |

Aturan:
- Jangan kirim ke diri sendiri.
- Notifikasi berisi link ke detail pengajuan.
- Unread count di layout.
- Notifikasi lama tetap tersimpan.

---

## 11. Laporan & export

### 11.1 Query dasar

- Hanya pengajuan dengan `submitted_at` tidak null.
- Default exclude `DRAFT`.
- Filter tanggal berdasarkan `submitted_at`.
- Filter departemen dan status.
- Eager load `user`, `department`, `items.category`.

### 11.2 Ringkasan

- Jumlah pengajuan.
- Total nominal (`sum(total_amount)`).
- Total dibayar (`sum(total_amount)` status `PAID`).
- Rata-rata hari dari `submitted_at` ke `paid_at` untuk status `PAID`.

### 11.3 Rekap

- Per departemen: jumlah, total, dibayar.
- Per kategori: jumlah item, total item (dari `reimbursement_items`).
- Tren 6 bulan: total submit dan total bayar per bulan.

### 11.4 Export

Gunakan `maatwebsite/excel`.

- `App\Exports\ReimbursementsExport`.
- Filter yang sama dengan halaman laporan.
- Format `.xlsx` dan `.csv`.
- Nama file: `laporan-reimbust-{YYYYMMDD}.xlsx`.
- Hanya finance/admin yang boleh export.

---

## 12. Keamanan & penyimpanan file

1. Semua route selain login memakai middleware `auth`.
2. Semua aksi memakai policy atau pengecekan role di server.
3. CSRF aktif (default Laravel).
4. Password memakai bcrypt (default).
5. Rate limit login (default Breeze).
6. Validasi upload: MIME, ekstensi, ukuran.
7. Simpan file di disk `local`, bukan `public`.
8. Sajikan file lewat `AttachmentController@show` setelah cek policy.
9. Gunakan `Storage::disk('local')->response(...)` dengan disposition inline.
10. Jangan tampilkan path file ke user.
11. Jangan izinkan upload file executable/script.
12. Activity log tidak memiliki route edit/delete.
13. Gunakan `@csrf` dan method spoofing pada semua form.
14. Escape komentar dengan Blade `{{ }}`; jangan pakai `{!! !!}` untuk input user.
15. Jangan menampilkan error sensitif di production.

---

## 13. Seed data

### 13.1 Akun demo

Semua password: `password`.

| Email | Nama | Role | Departemen | Atasan |
|---|---|---|---|---|
| admin@reimbust.test | Admin Reimbust | admin | - | - |
| budi@reimbust.test | Budi Santoso | approver | Operasional | - |
| rudi@reimbust.test | Rudi Hartono | employee | Operasional | Budi |
| siti@reimbust.test | Siti Aminah | employee | Marketing | Budi |
| sari@reimbust.test | Sari Wulandari | finance | Keuangan | - |
| kasir@reimbust.test | Andi Pratama | cashier | Keuangan | - |

### 13.2 Master data

Departemen:
- Operasional (`OPS`)
- Keuangan (`FIN`)
- Marketing (`MKT`)
- IT (`IT`)

Kategori:
- Transportasi (`TRN`, wajib bukti)
- Akomodasi (`HTL`, wajib bukti)
- Konsumsi (`KON`, wajib bukti)
- Lainnya (`LAIN`, wajib bukti)

### 13.3 Contoh pengajuan

Buat minimal 6 pengajuan:

1. Rudi — "Perjalanan Dinas Yogyakarta" — 3 item — `SUBMITTED` — submit 2 hari lalu.
2. Rudi — "Kunjungan Client Bandung" — 2 item — `PAID` — submit 10 hari lalu, bayar 8 hari lalu, dengan bukti pembayaran.
3. Siti — "Seminar Marketing" — 2 item — `REVISION` dari finance — 1 hari lalu.
4. Siti — "Transport Bulanan" — 1 item — `VERIFIED` — 3 hari lalu.
5. Rudi — "Persiapan Trip Surabaya" — 1 item — `DRAFT`.
6. Siti — "Pengeluaran Tidak Sesuai Kebijakan" — 1 item — `REJECTED`.

Setiap item yang berstatus minimal `SUBMITTED` harus punya lampiran placeholder. Buat placeholder PNG sederhana di `storage/app/private/receipts/...` (gunakan GD jika tersedia; jika tidak, gunakan base64 PNG 1x1). Tujuannya agar preview dan unduhan bisa diuji.

Seed juga activity log yang sesuai dengan status, dan minimal 2 notifikasi belum dibaca.

---

## 14. Urutan implementasi

Kerjakan berurutan. Jangan lanjut sebelum fase sebelumnya bisa dijalankan.

### Fase 1 — Fondasi
1. Scaffold Laravel di direktori proyek.
2. Konfigurasi `.env` MySQL, timezone, locale.
3. Install Breeze Blade, Tailwind, Alpine.
4. Hapus registrasi publik.
5. Buat migration, model, enum, relasi, factory.
6. Buat seeder master data + akun demo.
7. Buat layout, sidebar, topbar, komponen dasar.
8. Buat middleware role dan policy dasar.

**Output:** login berfungsi, dashboard kosong, master data ter-seed.

### Fase 2 — Pengajuan pegawai
1. CRUD draf pengajuan.
2. Form item dinamis (Alpine).
3. Upload dan preview bukti.
4. Perhitungan total.
5. Validasi draft vs submit.
6. Daftar dan detail pengajuan.
7. Activity log dasar.
8. Submit dan cancel.

**Output:** pegawai bisa membuat, menyimpan draf, dan mengajukan.

### Fase 3 — Workflow
1. Antrean approval.
2. Approve / return / reject.
3. Antrean finance + checklist verifikasi.
4. Verify / return / reject.
5. Antrean pembayaran.
6. Form pembayaran + **upload bukti wajib**.
7. Tampilan bukti pembayaran untuk pemilik.
8. Semua transisi lewat service + activity log.

**Output:** alur end-to-end berjalan.

### Fase 4 — Visibilitas
1. Timeline.
2. Komentar.
3. Notifikasi database.
4. Badge unread.
5. Aging badge.
6. Dashboard per peran.

**Output:** semua pihak bisa memantau dan berkomunikasi.

### Fase 5 — Laporan & admin
1. Halaman laporan + filter.
2. Ringkasan, rekap, grafik Chart.js.
3. Export Excel dan CSV.
4. CRUD admin user/departemen/kategori.
5. Toggle aktif.

**Output:** manajemen dan admin berfungsi.

### Fase 6 — Polish & QA
1. Empty state, error state, flash message.
2. Responsive mobile.
3. Pesan validasi bahasa Indonesia.
4. Feature test.
5. README.
6. Cek `npm run build`.

**Output:** siap demo.

---

## 15. Acceptance criteria

### 15.1 Pegawai
- Bisa membuat draf dengan 1–50 item.
- Bisa mengunggah beberapa bukti per item.
- Tidak bisa submit jika item wajib bukti belum punya lampiran.
- Bisa melihat status, timeline, komentar, dan notifikasi.
- Bisa merevisi dan mengajukan ulang.
- Bisa membatalkan pengajuan yang belum diproses finance.
- Bisa melihat dan mengunduh bukti pembayaran setelah dibayar.

### 15.2 Atasan
- Hanya melihat pengajuan bawahan langsung.
- Bisa approve, return, reject saat status `SUBMITTED`.
- Return/reject tanpa catatan ditolak.
- Tidak bisa memproses pengajuannya sendiri.
- Menerima notifikasi saat ada pengajuan baru.

### 15.3 Finance
- Hanya bisa verify saat status `APPROVED`.
- Verify tanpa 3 checklist ditolak.
- Bisa return/reject dengan catatan.
- Bisa melihat semua versi lampiran.
- Bisa melihat dan mengekspor laporan.

### 15.4 Kasir
- Hanya melihat pengajuan `VERIFIED`/`PAID`.
- **Tidak bisa menandai dibayar tanpa mengunggah bukti pembayaran.**
- Bukti tersimpan dan tampil di detail.
- Status berubah menjadi `PAID` dan pemilik mendapat notifikasi.

### 15.5 Keamanan
- Pegawai A tidak bisa membuka pengajuan pegawai B (403).
- Pegawai tidak bisa mengunduh lampiran pengajuan orang lain.
- Kasir tidak bisa verify.
- Finance tidak bisa pay.
- Admin tidak bisa approve/verify/pay.
- File tidak bisa diakses tanpa login.

### 15.6 Laporan
- Total laporan cocok dengan data.
- Filter periode/departemen/status bekerja.
- Export CSV dan Excel menghasilkan file yang bisa dibuka.
- Draft tidak ikut.
- Hanya finance/admin yang bisa akses.

---

## 16. Definition of done

1. `php artisan migrate:fresh --seed` berhasil tanpa error.
2. `npm run build` berhasil.
3. Semua akun demo bisa login.
4. Alur lengkap berjalan: Rudi submit → Budi approve → Sari verify → Kasir bayar + upload bukti → Rudi lihat bukti.
5. Revisi dari atasan dan finance berjalan sesuai aturan.
6. Tidak ada OCR/ICR atau API eksternal.
7. Semua nominal tampil dalam format rupiah.
8. Responsive di lebar 375px dan 1440px.
9. Feature test utama lulus.
10. README berisi cara setup, akun demo, dan alur uji.

---

## 17. Setup & konfigurasi

### 17.1 Prasyarat

- PHP 8.2+ dengan ekstensi `pdo_mysql`, `mbstring`, `fileinfo`, `gd` (opsional), `zip`.
- Composer 2.
- Node.js 20+ dan npm.
- MySQL 8 / MariaDB via XAMPP atau Laragon.
- phpMyAdmin.

### 17.2 Database

1. Buka phpMyAdmin.
2. Buat database `reimbust` dengan collation `utf8mb4_unicode_ci`.
3. Konfigurasi `.env`:

```env
APP_NAME=Reimbust
APP_ENV=local
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=reimbust
DB_USERNAME=root
DB_PASSWORD=
```

4. Set timezone di `config/app.php`:

```php
'timezone' => 'Asia/Jakarta',
'locale' => 'id',
```

### 17.3 Perintah

```bash
composer install
php artisan key:generate
php artisan migrate:fresh --seed
npm install
npm run dev
php artisan serve
```

Jika direktori proyek sudah berisi `kiro.md`, scaffold Laravel di folder sementara lalu pindahkan isinya ke direktori proyek tanpa menimpa `kiro.md`.

### 17.4 Konfigurasi aplikasi

Buat `config/reimbust.php`:

```php
return [
    'aging_warning_days' => 3,
    'aging_danger_days' => 5,
    'max_file_kb' => 5120,
    'max_items' => 50,
    'max_files_per_item' => 5,
    'max_comment_length' => 1000,
];
```

---

## 18. Di luar cakupan

Jangan implementasikan:

- OCR/ICR atau ekstraksi struk otomatis.
- Aplikasi mobile native.
- Registrasi publik.
- Login Google/SSO.
- Notifikasi email/SMTP.
- Approval multi-level atau paralel.
- Delegasi approval.
- Pembayaran sebagian/cicilan.
- Integrasi bank, payroll, ERP, atau accounting.
- Booking tiket/hotel.
- Multi-perusahaan/multi-tenant.
- Budget/limit per kategori.
- Tanda tangan digital.
- Export PDF.
- Dark mode.
- API publik.

---

## 19. Konvensi kode

1. Nama kelas, method, variabel, route, dan kolom database dalam bahasa Inggris.
2. Teks UI dan pesan validasi dalam bahasa Indonesia.
3. Gunakan PSR-12 dan Laravel Pint.
4. Gunakan Form Request untuk validasi, Service untuk logika bisnis, Policy untuk otorisasi.
5. Jangan menaruh logika bisnis di Blade atau controller.
6. Gunakan `DB::transaction()` untuk operasi multi-tabel dan perubahan status.
7. Gunakan eager loading untuk menghindari N+1.
8. Gunakan route model binding.
9. Gunakan named routes di Blade (`route('reimbursements.show', $r)`).
10. Gunakan komponen Blade untuk elemen berulang.
11. Jangan gunakan `{!! !!}` untuk data dari user.
12. Gunakan enum cast, bukan string mentah.
13. Gunakan `now()` dengan timezone aplikasi.
14. Gunakan pagination dengan `withQueryString()`.
15. Tulis feature test untuk aturan penting.

---

## 20. Ringkasan alur demo

1. Login sebagai `rudi@reimbust.test`.
2. Buat pengajuan "Perjalanan Dinas Yogyakarta" dengan 3 item dan bukti.
3. Ajukan.
4. Login sebagai `budi@reimbust.test`, setujui.
5. Login sebagai `sari@reimbust.test`, centang checklist dan verifikasi.
6. Login sebagai `kasir@reimbust.test`, buka pembayaran, isi data, **unggah bukti transfer**, konfirmasi.
7. Login kembali sebagai Rudi, lihat status `Dibayar` dan unduh bukti pembayaran.
8. Login sebagai Sari, buka laporan, filter bulan ini, export Excel.

---

**Akhir dokumen.**
