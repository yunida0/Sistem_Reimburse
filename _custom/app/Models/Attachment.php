<?php

namespace App\Models;

use App\Enums\AttachmentKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    protected $fillable = [
        'reimbursement_id', 'item_id', 'kind',
        'original_name', 'stored_path', 'mime_type',
        'size_bytes', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'kind' => AttachmentKind::class,
        ];
    }

    public function reimbursement(): BelongsTo
    {
        return $this->belongsTo(Reimbursement::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ReimbursementItem::class, 'item_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }
}
