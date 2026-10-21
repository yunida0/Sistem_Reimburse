<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function show(Attachment $attachment)
    {
        $this->authorize('downloadAttachment', $attachment->reimbursement);

        return Storage::disk('local')->response(
            $attachment->stored_path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type]
        );
    }

    public function destroy(Attachment $attachment)
    {
        $reimbursement = $attachment->reimbursement;
        $this->authorize('update', $reimbursement);

        Storage::disk('local')->delete($attachment->stored_path);
        $attachment->delete();

        // Recalculate (attachment delete tidak ubah total, tapi item boleh kosong)
        return back()->with('success', 'Lampiran berhasil dihapus.');
    }
}
