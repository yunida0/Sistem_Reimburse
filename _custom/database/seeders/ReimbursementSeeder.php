<?php

namespace Database\Seeders;

use App\Enums\AttachmentKind;
use App\Enums\PaymentMethod;
use App\Enums\ReimbursementStatus;
use App\Enums\RevisionFrom;
use App\Models\Attachment;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Reimbursement;
use App\Models\ReimbursementItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ReimbursementSeeder extends Seeder
{
    public function run(): void
    {
        $rudi  = User::where('email', 'rudi@reimbust.test')->first();
        $siti  = User::where('email', 'siti@reimbust.test')->first();
        $budi  = User::where('email', 'budi@reimbust.test')->first();
        $sari  = User::where('email', 'sari@reimbust.test')->first();
        $kasir = User::where('email', 'kasir@reimbust.test')->first();

        $trn  = Category::where('code', 'TRN')->first();
        $htl  = Category::where('code', 'HTL')->first();
        $kon  = Category::where('code', 'KON')->first();
        $lain = Category::where('code', 'LAIN')->first();

        // Buat placeholder PNG
        $placeholder = $this->makePlaceholderPng();

        // ── 1. Rudi — SUBMITTED (2 hari lalu) ─────────────────────────────
        $r1 = $this->createReimbursement($rudi, [
            'title'       => 'Perjalanan Dinas Yogyakarta',
            'status'      => ReimbursementStatus::Submitted,
            'submitted_at'=> now()->subDays(2),
        ]);
        $i1a = $this->addItem($r1, $trn,  now()->subDays(3)->toDateString(), 'Tiket Kereta PP', 'KAI',       350000, 0);
        $i1b = $this->addItem($r1, $htl,  now()->subDays(3)->toDateString(), 'Hotel 2 malam',  'Whiz Hotel', 650000, 1);
        $i1c = $this->addItem($r1, $kon,  now()->subDays(2)->toDateString(), 'Makan siang tim', null,        120000, 2);
        $this->attachPlaceholder($r1, $i1a, $placeholder, $rudi, AttachmentKind::Receipt);
        $this->attachPlaceholder($r1, $i1b, $placeholder, $rudi, AttachmentKind::Receipt);
        $this->attachPlaceholder($r1, $i1c, $placeholder, $rudi, AttachmentKind::Receipt);
        $r1->update(['total_amount' => 1120000]);
        $this->log($r1, $rudi, 'submit', null, 'SUBMITTED');

        // Notifikasi ke Budi
        $budi->notify(new \App\Notifications\ReimbursementNotification(
            "Pengajuan Baru: {$r1->code}",
            "Rudi Hartono mengajukan \"{$r1->title}\" untuk persetujuan.",
            $r1, 'info'
        ));

        // ── 2. Rudi — PAID (10 hari lalu submit, 8 hari lalu bayar) ──────
        $r2 = $this->createReimbursement($rudi, [
            'title'            => 'Kunjungan Client Bandung',
            'status'           => ReimbursementStatus::Paid,
            'submitted_at'     => now()->subDays(10),
            'approved_at'      => now()->subDays(9),
            'verified_at'      => now()->subDays(8)->addHours(2),
            'paid_at'          => now()->subDays(8),
            'payment_method'   => PaymentMethod::Transfer,
            'payment_reference'=> 'TRF-2026-001',
            'payment_note'     => 'Pembayaran via BCA',
        ]);
        $i2a = $this->addItem($r2, $trn, now()->subDays(10)->toDateString(), 'Toll + Bensin', null, 180000, 0);
        $i2b = $this->addItem($r2, $kon, now()->subDays(10)->toDateString(), 'Makan malam client', 'Sate Khas Senayan', 450000, 1);
        $this->attachPlaceholder($r2, $i2a, $placeholder, $rudi, AttachmentKind::Receipt);
        $this->attachPlaceholder($r2, $i2b, $placeholder, $rudi, AttachmentKind::Receipt);
        $this->attachPlaceholder($r2, null, $placeholder, $kasir, AttachmentKind::PaymentProof);
        $r2->update(['total_amount' => 630000]);
        $this->log($r2, $rudi,  'submit',            null,        'SUBMITTED');
        $this->log($r2, $budi,  'approve',           'SUBMITTED', 'APPROVED');
        $this->log($r2, $sari,  'verify',            'APPROVED',  'VERIFIED');
        $this->log($r2, $kasir, 'pay',               'VERIFIED',  'PAID');

        // ── 3. Siti — REVISION dari finance (1 hari lalu) ─────────────────
        $r3 = $this->createReimbursement($siti, [
            'title'         => 'Seminar Marketing',
            'status'        => ReimbursementStatus::Revision,
            'revision_from' => RevisionFrom::Finance,
            'submitted_at'  => now()->subDays(5),
            'approved_at'   => now()->subDays(3),
        ]);
        $i3a = $this->addItem($r3, cat: $trn, date: now()->subDays(5)->toDateString(), desc: 'Transportasi ke venue', vendor: null, amount: 75000, sort: 0);
        $i3b = $this->addItem($r3, cat: $kon, date: now()->subDays(5)->toDateString(), desc: 'Konsumsi seminar', vendor: null, amount: 100000, sort: 1);
        $this->attachPlaceholder($r3, $i3a, $placeholder, $siti, AttachmentKind::Receipt);
        $this->attachPlaceholder($r3, $i3b, $placeholder, $siti, AttachmentKind::Receipt);
        $r3->update(['total_amount' => 175000]);
        $this->log($r3, $siti, 'submit',           null,        'SUBMITTED');
        $this->log($r3, $budi, 'approve',          'SUBMITTED', 'APPROVED');
        $this->log($r3, $sari, 'return_by_finance', 'APPROVED',  'REVISION', 'Nominal konsumsi perlu dilampirkan struk yang lebih jelas.');

        // ── 4. Siti — VERIFIED (3 hari lalu) ──────────────────────────────
        $r4 = $this->createReimbursement($siti, [
            'title'        => 'Transport Bulanan',
            'status'       => ReimbursementStatus::Verified,
            'submitted_at' => now()->subDays(5),
            'approved_at'  => now()->subDays(4),
            'verified_at'  => now()->subDays(3),
        ]);
        $i4a = $this->addItem($r4, $trn, now()->subDays(5)->toDateString(), 'Ojek online Oktober', null, 280000, 0);
        $this->attachPlaceholder($r4, $i4a, $placeholder, $siti, AttachmentKind::Receipt);
        $r4->update(['total_amount' => 280000]);
        $this->log($r4, $siti, 'submit',  null,        'SUBMITTED');
        $this->log($r4, $budi, 'approve', 'SUBMITTED', 'APPROVED');
        $this->log($r4, $sari, 'verify',  'APPROVED',  'VERIFIED');

        // Notifikasi ke kasir
        $kasir->notify(new \App\Notifications\ReimbursementNotification(
            "Pengajuan Siap Dibayar: {$r4->code}",
            "Pengajuan \"{$r4->title}\" dari {$siti->name} siap dibayar.",
            $r4, 'info'
        ));

        // ── 5. Rudi — DRAFT ────────────────────────────────────────────────
        $r5 = $this->createReimbursement($rudi, [
            'title'  => 'Persiapan Trip Surabaya',
            'status' => ReimbursementStatus::Draft,
        ]);
        $this->addItem($r5, $trn, now()->subDays(1)->toDateString(), 'Survey rute kereta', null, 50000, 0);
        $r5->update(['total_amount' => 50000]);

        // ── 6. Siti — REJECTED ─────────────────────────────────────────────
        $r6 = $this->createReimbursement($siti, [
            'title'        => 'Pengeluaran Tidak Sesuai Kebijakan',
            'status'       => ReimbursementStatus::Rejected,
            'submitted_at' => now()->subDays(7),
        ]);
        $i6a = $this->addItem($r6, $lain, now()->subDays(8)->toDateString(), 'Pembelian barang pribadi', null, 500000, 0);
        $this->attachPlaceholder($r6, $i6a, $placeholder, $siti, AttachmentKind::Receipt);
        $r6->update(['total_amount' => 500000]);
        $this->log($r6, $siti, 'submit',           null,        'SUBMITTED');
        $this->log($r6, $budi, 'reject_by_approver','SUBMITTED', 'REJECTED', 'Pengeluaran tidak sesuai kebijakan perusahaan.');
    }

    private function createReimbursement(User $user, array $data): Reimbursement
    {
        $r = Reimbursement::create([
            'user_id'          => $user->id,
            'department_id'    => $user->department_id,
            'title'            => $data['title'],
            'description'      => $data['description'] ?? null,
            'total_amount'     => 0,
            'status'           => $data['status'],
            'revision_from'    => $data['revision_from'] ?? null,
            'submitted_at'     => $data['submitted_at'] ?? null,
            'approved_at'      => $data['approved_at'] ?? null,
            'verified_at'      => $data['verified_at'] ?? null,
            'paid_at'          => $data['paid_at'] ?? null,
            'payment_method'   => $data['payment_method'] ?? null,
            'payment_reference'=> $data['payment_reference'] ?? null,
            'payment_note'     => $data['payment_note'] ?? null,
        ]);

        // Generate kode
        $year = now()->year;
        $r->update(['code' => sprintf('RB-%d-%04d', $year, $r->id)]);

        return $r;
    }

    private function addItem(
        Reimbursement $r,
        Category $cat,
        string $date,
        string $desc,
        ?string $vendor,
        int $amount,
        int $sort
    ): ReimbursementItem {
        return ReimbursementItem::create([
            'reimbursement_id' => $r->id,
            'category_id'      => $cat->id,
            'expense_date'     => $date,
            'description'      => $desc,
            'vendor'           => $vendor,
            'amount'           => $amount,
            'sort_order'       => $sort,
        ]);
    }

    private function attachPlaceholder(
        Reimbursement $r,
        ?ReimbursementItem $item,
        string $pngContent,
        User $uploader,
        AttachmentKind $kind
    ): void {
        $folder  = $kind === AttachmentKind::Receipt ? "receipts/{$r->id}" : "payments/{$r->id}";
        $filename = 'placeholder_' . uniqid() . '.png';
        $path     = $folder . '/' . $filename;

        Storage::disk('local')->put($path, $pngContent);

        Attachment::create([
            'reimbursement_id' => $r->id,
            'item_id'          => $item?->id,
            'kind'             => $kind,
            'original_name'    => 'bukti.png',
            'stored_path'      => $path,
            'mime_type'        => 'image/png',
            'size_bytes'       => strlen($pngContent),
            'uploaded_by'      => $uploader->id,
        ]);
    }

    private function log(Reimbursement $r, User $actor, string $action, ?string $from, string $to, ?string $note = null): void
    {
        ActivityLog::create([
            'reimbursement_id' => $r->id,
            'actor_id'         => $actor->id,
            'action'           => $action,
            'from_status'      => $from,
            'to_status'        => $to,
            'note'             => $note,
        ]);
    }

    private function makePlaceholderPng(): string
    {
        // Fallback: PNG 1x1 transparan jika GD tidak tersedia
        if (!function_exists('imagecreatetruecolor')) {
            return base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
            );
        }

        $width  = 800;
        $height = 600;
        $img    = imagecreatetruecolor($width, $height);

        $white = imagecolorallocate($img, 255, 255, 255);
        $blue  = imagecolorallocate($img, 37, 99, 235);
        $slate = imagecolorallocate($img, 100, 116, 139);
        $light = imagecolorallocate($img, 226, 232, 240);

        imagefilledrectangle($img, 0, 0, $width, $height, $white);
        imagefilledrectangle($img, 0, 0, $width, 90, $blue);
        imagerectangle($img, 20, 20, $width - 20, $height - 20, $blue);

        for ($y = 230; $y <= 480; $y += 50) {
            imageline($img, 60, $y, $width - 60, $y, $light);
        }

        $font = 'C:/Windows/Fonts/arial.ttf';

        if (is_file($font) && function_exists('imagettftext')) {
            imagettftext($img, 30, 0, 40, 62, $white, $font, 'BUKTI TRANSAKSI');
            imagettftext($img, 22, 0, 60, 150, $blue, $font, 'Reimbust - Dokumen Demo');
            imagettftext($img, 15, 0, 60, 195, $slate, $font, 'Lampiran contoh untuk pengujian preview dan unduhan.');
            imagettftext($img, 15, 0, 60, 545, $slate, $font, 'File ini bukan bukti transaksi sebenarnya.');
        } else {
            imagestring($img, 5, 40, 45, 'BUKTI TRANSAKSI', $white);
            imagestring($img, 4, 60, 140, 'Reimbust - Dokumen Demo', $blue);
        }

        ob_start();
        imagepng($img);
        $data = ob_get_clean();
        imagedestroy($img);

        return $data;
    }
}
