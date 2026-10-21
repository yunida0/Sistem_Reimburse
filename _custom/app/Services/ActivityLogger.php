<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Reimbursement;
use App\Models\User;

class ActivityLogger
{
    public function log(
        Reimbursement $reimbursement,
        ?User $actor,
        string $action,
        ?string $from,
        ?string $to,
        ?string $note = null,
        array $meta = []
    ): void {
        ActivityLog::create([
            'reimbursement_id' => $reimbursement->id,
            'actor_id'         => $actor?->id,
            'action'           => $action,
            'from_status'      => $from,
            'to_status'        => $to,
            'note'             => $note,
            'meta'             => empty($meta) ? null : $meta,
        ]);
    }
}
