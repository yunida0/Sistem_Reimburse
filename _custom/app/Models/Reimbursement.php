<?php

namespace App\Models;

use App\Enums\AttachmentKind;
use App\Enums\PaymentMethod;
use App\Enums\ReimbursementStatus;
use App\Enums\RevisionFrom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reimbursement extends Model
{
    protected $fillable = [
        'code', 'user_id', 'department_id', 'title', 'description',
        'total_amount', 'status', 'revision_from',
        'submitted_at', 'approved_at', 'verified_at', 'paid_at',
        'payment_method', 'payment_reference', 'payment_note',
    ];

    protected function casts(): array
    {
        return [
            'status'         => ReimbursementStatus::class,
            'revision_from'  => RevisionFrom::class,
            'payment_method' => PaymentMethod::class,
            'submitted_at'   => 'datetime',
            'approved_at'    => 'datetime',
            'verified_at'    => 'datetime',
            'paid_at'        => 'datetime',
        ];
    }

    // ── Relations ────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReimbursementItem::class)->orderBy('sort_order');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ActivityLog::class)->orderBy('created_at');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->orderBy('created_at');
    }

    public function paymentProofs(): HasMany
    {
        return $this->hasMany(Attachment::class)
                    ->where('kind', AttachmentKind::PaymentProof->value);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->status === ReimbursementStatus::Draft;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [
            ReimbursementStatus::Draft,
            ReimbursementStatus::Revision,
        ]);
    }

    public function agingDays(): int
    {
        return (int) $this->updated_at->diffInDays(now());
    }
}
