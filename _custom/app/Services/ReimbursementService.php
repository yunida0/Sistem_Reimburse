<?php

namespace App\Services;

use App\Enums\AttachmentKind;
use App\Enums\ReimbursementStatus;
use App\Models\Attachment;
use App\Models\Reimbursement;
use App\Models\ReimbursementItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReimbursementService
{
    public function createDraft(User $user, array $data): Reimbursement
    {
        $reimbursement = Reimbursement::create([
            'user_id'       => $user->id,
            'department_id' => $user->department_id,
            'title'         => $data['title'],
            'description'   => $data['description'] ?? null,
            'status'        => ReimbursementStatus::Draft,
        ]);

        return $reimbursement;
    }

    public function updateDraft(Reimbursement $reimbursement, array $data): void
    {
        $reimbursement->update([
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
        ]);
    }

    public function syncItems(Reimbursement $reimbursement, array $items): void
    {
        // Simpan ID item lama agar lampiran tidak terhapus
        $existingIds = $reimbursement->items()->pluck('id')->toArray();
        $newIds = [];

        foreach ($items as $index => $itemData) {
            // Skip baris kosong
            if (empty($itemData['description']) && empty($itemData['amount'])) {
                continue;
            }

            $item = ReimbursementItem::updateOrCreate(
                [
                    'id'               => $itemData['id'] ?? null,
                    'reimbursement_id' => $reimbursement->id,
                ],
                [
                    'reimbursement_id' => $reimbursement->id,
                    'category_id'      => $itemData['category_id'],
                    'expense_date'     => $itemData['expense_date'],
                    'description'      => $itemData['description'],
                    'vendor'           => $itemData['vendor'] ?? null,
                    'amount'           => $itemData['amount'],
                    'sort_order'       => $index,
                ]
            );

            $newIds[] = $item->id;
        }

        // Hapus item yang tidak ada lagi (hanya saat DRAFT)
        if ($reimbursement->status === ReimbursementStatus::Draft) {
            $toDelete = array_diff($existingIds, $newIds);
            if (!empty($toDelete)) {
                // Hapus attachment item dulu
                Attachment::whereIn('item_id', $toDelete)->each(function ($att) {
                    Storage::disk('local')->delete($att->stored_path);
                    $att->delete();
                });
                ReimbursementItem::whereIn('id', $toDelete)->delete();
            }
        }
    }

    public function storeUploadedReceipts(Reimbursement $reimbursement, array $itemsData, User $actor): array
    {
        $storedPaths = [];

        foreach ($itemsData as $index => $itemData) {
            if (empty($itemData['receipts'])) {
                continue;
            }

            // Cari item yang sesuai berdasarkan sort_order
            $item = $reimbursement->items()->where('sort_order', $index)->first();
            if (!$item) {
                continue;
            }

            foreach ($itemData['receipts'] as $file) {
                if (!$file instanceof UploadedFile) {
                    continue;
                }

                $attachment = $this->storeFile($file, $reimbursement, $item->id, AttachmentKind::Receipt, $actor);
                $storedPaths[] = $attachment->stored_path;
            }
        }

        return $storedPaths;
    }

    public function storeFile(
        UploadedFile $file,
        Reimbursement $reimbursement,
        ?int $itemId,
        AttachmentKind $kind,
        User $actor
    ): Attachment {
        $folder = $kind === AttachmentKind::Receipt
            ? "receipts/{$reimbursement->id}"
            : "payments/{$reimbursement->id}";

        $storedPath = $file->store($folder, 'local');

        return Attachment::create([
            'reimbursement_id' => $reimbursement->id,
            'item_id'          => $itemId,
            'kind'             => $kind,
            'original_name'    => $file->getClientOriginalName(),
            'stored_path'      => $storedPath,
            'mime_type'        => $file->getMimeType(),
            'size_bytes'       => $file->getSize(),
            'uploaded_by'      => $actor->id,
        ]);
    }

    public function recalculateTotal(Reimbursement $reimbursement): void
    {
        $total = $reimbursement->items()->sum('amount');
        $reimbursement->update(['total_amount' => $total]);
    }

    public function assertReadyToSubmit(Reimbursement $reimbursement): void
    {
        $reimbursement->load('items.category', 'items.attachments');

        $items = $reimbursement->items;

        if ($items->count() < 1) {
            throw ValidationException::withMessages([
                'items' => ['Minimal 1 item biaya harus diisi.'],
            ]);
        }

        if ($items->count() > config('reimbust.max_items', 50)) {
            throw ValidationException::withMessages([
                'items' => ['Maksimal ' . config('reimbust.max_items', 50) . ' item biaya.'],
            ]);
        }

        foreach ($items as $item) {
            if (!$item->expense_date || !$item->category_id || !$item->description || !$item->amount) {
                throw ValidationException::withMessages([
                    'items' => ['Semua kolom item biaya harus diisi.'],
                ]);
            }

            if ($item->expense_date->toDateString() > now()->toDateString()) {
                throw ValidationException::withMessages([
                    'items' => ['Tanggal pengeluaran tidak boleh melebihi hari ini.'],
                ]);
            }

            if ($item->category->requires_receipt && $item->attachments->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => ["Item \"{$item->description}\" wajib menyertakan bukti transaksi."],
                ]);
            }
        }
    }

    public function generateCode(Reimbursement $reimbursement): void
    {
        $year = now()->year;
        $code = sprintf('RB-%d-%04d', $year, $reimbursement->id);
        $reimbursement->update(['code' => $code]);
    }
}
