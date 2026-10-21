<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Reimbursement;
use App\Models\User;
use App\Notifications\ReimbursementNotification;

class NotificationService
{
    public function notifyManager(Reimbursement $r, User $actor, string $title, string $message, string $level = 'info'): void
    {
        $manager = $r->user->manager;
        if ($manager && $manager->id !== $actor->id) {
            $manager->notify(new ReimbursementNotification($title, $message, $r, $level));
        }
    }

    public function notifyOwner(Reimbursement $r, User $actor, string $title, string $message, string $level = 'info'): void
    {
        $owner = $r->user;
        if ($owner->id !== $actor->id) {
            $owner->notify(new ReimbursementNotification($title, $message, $r, $level));
        }
    }

    public function notifyFinanceTeam(Reimbursement $r, User $actor, string $title, string $message, string $level = 'info'): void
    {
        $financeUsers = User::where('role', UserRole::Finance->value)
                            ->where('is_active', true)
                            ->get();

        foreach ($financeUsers as $user) {
            if ($user->id !== $actor->id) {
                $user->notify(new ReimbursementNotification($title, $message, $r, $level));
            }
        }
    }

    public function notifyCashierTeam(Reimbursement $r, User $actor, string $title, string $message, string $level = 'info'): void
    {
        $cashiers = User::where('role', UserRole::Cashier->value)
                        ->where('is_active', true)
                        ->get();

        foreach ($cashiers as $user) {
            if ($user->id !== $actor->id) {
                $user->notify(new ReimbursementNotification($title, $message, $r, $level));
            }
        }
    }
}
