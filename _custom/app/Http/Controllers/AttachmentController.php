<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function show(Attachment $attachment)
    {
        // Eager-load relasi agar policy tidak kena N+1 / null
        $reimbursement = $attachment->load('reimbursement.user')->reimbursement;

        $this->authorize('downloadAttachment', $reimbursement);

        $disk = Storage::disk('local');

        abort_unless($disk->exists($attachment->stored_path), 404, 'File tidak ditemukan.');

        // Tentukan disposition: inline untuk gambar/PDF, attachment untuk lainnya
        $isInline = str_starts_with($attachment->mime_type, 'image/') 
                 || $attachment->mime_type === 'application/pdf';

        return $disk->response(
            $attachment->stored_path,
            $attachment->original_name,
            [
                'Content-Type'        => $attachment->mime_type,
                'Content-Disposition' => ($isInline ? 'inline' : 'attachment')
                    . '; filename="' . addslashes($attachment->original_name) . '"',
            ]
        );
    }

    public function destroy(Attachment $attachment)
    {
        $reimbursement = $attachment->load('reimbursement')->reimbursement;
        $this->authorize('update', $reimbursement);

        Storage::disk('local')->delete($attachment->stored_path);
        $attachment->delete();

        return back()->with('success', 'Lampiran berhasil dihapus.');
    }
}
