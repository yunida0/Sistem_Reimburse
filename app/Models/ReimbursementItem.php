<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReimbursementItem extends Model
{
    protected $fillable = [
        'reimbursement_id', 'category_id',
        'expense_date', 'description', 'vendor',
        'amount', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
        ];
    }

    public function reimbursement(): BelongsTo
    {
        return $this->belongsTo(Reimbursement::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'item_id');
    }
}
