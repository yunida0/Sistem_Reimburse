<?php

namespace App\Policies;

use App\Enums\ReimbursementStatus;
use App\Enums\UserRole;
use App\Models\Reimbursement;
use App\Models\User;

class ReimbursementPolicy
{
    public function view(User $user, Reimbursement $reimbursement): bool
    {
        // Owner selalu boleh lihat
        if ($user->id === $reimbursement->user_id) {
            return true;
        }

        // Atasan langsung owner — pastikan relasi user ter-load
        $owner = $reimbursement->relationLoaded('user')
            ? $reimbursement->user
            : $reimbursement->load('user')->user;

        if ($owner && $user->isManagerOf($owner)) {
            return true;
        }

        // Finance bisa lihat semua
        if ($user->hasRole(UserRole::Finance)) {
            return true;
        }

        // Admin bisa lihat semua
        if ($user->hasRole(UserRole::Admin)) {
            return true;
        }

        // Kasir hanya VERIFIED/PAID
        if ($user->hasRole(UserRole::Cashier)) {
            return in_array($reimbursement->status, [
                ReimbursementStatus::Verified,
                ReimbursementStatus::Paid,
            ]);
        }

        return false;
    }

    public function update(User $user, Reimbursement $reimbursement): bool
    {
        return $user->id === $reimbursement->user_id
            && $reimbursement->isEditable();
    }

    public function submit(User $user, Reimbursement $reimbursement): bool
    {
        return $user->id === $reimbursement->user_id
            && $reimbursement->isEditable();
    }

    public function cancel(User $user, Reimbursement $reimbursement): bool
    {
        if ($user->id !== $reimbursement->user_id) {
            return false;
        }

        return in_array($reimbursement->status, [
            ReimbursementStatus::Draft,
            ReimbursementStatus::Submitted,
            ReimbursementStatus::Revision,
        ]);
    }

    public function comment(User $user, Reimbursement $reimbursement): bool
    {
        return $this->view($user, $reimbursement);
    }

    public function approve(User $user, Reimbursement $reimbursement): bool
    {
        $owner = $reimbursement->relationLoaded('user')
            ? $reimbursement->user
            : $reimbursement->load('user')->user;

        return $owner
            && $user->id === $owner->manager_id
            && $reimbursement->status === ReimbursementStatus::Submitted
            && $user->id !== $reimbursement->user_id;
    }

    public function returnByApprover(User $user, Reimbursement $reimbursement): bool
    {
        return $this->approve($user, $reimbursement);
    }

    public function rejectByApprover(User $user, Reimbursement $reimbursement): bool
    {
        return $this->approve($user, $reimbursement);
    }

    public function verify(User $user, Reimbursement $reimbursement): bool
    {
        return $user->hasRole(UserRole::Finance)
            && $reimbursement->status === ReimbursementStatus::Approved;
    }

    public function returnByFinance(User $user, Reimbursement $reimbursement): bool
    {
        return $this->verify($user, $reimbursement);
    }

    public function rejectByFinance(User $user, Reimbursement $reimbursement): bool
    {
        return $this->verify($user, $reimbursement);
    }

    public function pay(User $user, Reimbursement $reimbursement): bool
    {
        return $user->hasRole(UserRole::Cashier)
            && $reimbursement->status === ReimbursementStatus::Verified;
    }

    public function downloadAttachment(User $user, Reimbursement $reimbursement): bool
    {
        return $this->view($user, $reimbursement);
    }
}
