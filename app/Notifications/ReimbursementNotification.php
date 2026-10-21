<?php

namespace App\Notifications;

use App\Models\Reimbursement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReimbursementNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public Reimbursement $reimbursement,
        public string $level = 'info',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'              => $this->title,
            'message'            => $this->message,
            'url'                => route('reimbursements.show', $this->reimbursement),
            'level'              => $this->level,
            'reimbursement_id'   => $this->reimbursement->id,
            'reimbursement_code' => $this->reimbursement->code,
        ];
    }
}
