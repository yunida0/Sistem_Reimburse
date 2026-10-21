<?php

namespace App\Services;

use App\Enums\AttachmentKind;
use App\Enums\ReimbursementStatus;
use App\Enums\RevisionFrom;
use App\Models\Reimbursement;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class WorkflowService
{
    public function __construct(
        protected ActivityLogger $logger,
        protected NotificationService $notifier,
        protected ReimbursementService $reimbursementService,
    ) {}

    public function submit(Reimbursement $r, User $actor): void
    {
        DB::transaction(function () use ($r, $actor) {
            $r->lockForUpdate()->find($r->id);

            $this->reimbursementService->assertReadyToSubmit($r);

            // Generate code jika belum ada
            if (!$r->code) {
                $this->reimbursementService->generateCode($r);
            }

            $from = $r->status->value;

            // Resubmit setelah revisi finance → kembali ke APPROVED
            $to = ($r->revision_from === RevisionFrom::Finance)
                ? ReimbursementStatus::Approved
                : ReimbursementStatus::Submitted;

            $r->update([
                'status'       => $to,
                'submitted_at' => now(),
                'revision_from'=> null,
            ]);

            $this->logger->log($r, $actor, 'submit', $from, $to->value);

            if ($to === ReimbursementStatus::Submitted) {
                $this->notifier->notifyManager(
                    $r, $actor,
                    "Pengajuan Baru: {$r->code}",
                    "{$actor->name} mengajukan \"{$r->title}\" untuk persetujuan.",
                    'info'
                );
            } else {
                // Resubmit ke finance
                $this->notifier->notifyFinanceTeam(
                    $r, $actor,
                    "Revisi Selesai: {$r->code}",
                    "{$actor->name} telah menyelesaikan revisi \"{$r->title}\".",
                    'info'
                );
            }
        });
    }

    public function approve(Reimbursement $r, User $actor): void
    {
        DB::transaction(function () use ($r, $actor) {
            $r->lockForUpdate()->find($r->id);
            $from = $r->status->value;

            $r->update([
                'status'      => ReimbursementStatus::Approved,
                'approved_at' => now(),
            ]);

            $this->logger->log($r, $actor, 'approve', $from, ReimbursementStatus::Approved->value);

            $this->notifier->notifyOwner(
                $r, $actor,
                "Pengajuan Disetujui: {$r->code}",
                "Pengajuan \"{$r->title}\" Anda telah disetujui oleh {$actor->name}.",
                'success'
            );

            $this->notifier->notifyFinanceTeam(
                $r, $actor,
                "Pengajuan Siap Diverifikasi: {$r->code}",
                "Pengajuan \"{$r->title}\" dari {$r->user->name} telah disetujui dan menunggu verifikasi.",
                'info'
            );
        });
    }

    public function returnByApprover(Reimbursement $r, User $actor, string $note): void
    {
        DB::transaction(function () use ($r, $actor, $note) {
            $r->lockForUpdate()->find($r->id);
            $from = $r->status->value;

            $r->update([
                'status'       => ReimbursementStatus::Revision,
                'revision_from'=> RevisionFrom::Approver,
                'approved_at'  => null,
            ]);

            $this->logger->log($r, $actor, 'return_by_approver', $from, ReimbursementStatus::Revision->value, $note);

            $this->notifier->notifyOwner(
                $r, $actor,
                "Perlu Revisi: {$r->code}",
                "Pengajuan \"{$r->title}\" Anda diminta revisi oleh {$actor->name}.",
                'warning'
            );
        });
    }

    public function rejectByApprover(Reimbursement $r, User $actor, string $note): void
    {
        DB::transaction(function () use ($r, $actor, $note) {
            $r->lockForUpdate()->find($r->id);
            $from = $r->status->value;

            $r->update(['status' => ReimbursementStatus::Rejected]);

            $this->logger->log($r, $actor, 'reject_by_approver', $from, ReimbursementStatus::Rejected->value, $note);

            $this->notifier->notifyOwner(
                $r, $actor,
                "Pengajuan Ditolak: {$r->code}",
                "Pengajuan \"{$r->title}\" Anda ditolak oleh {$actor->name}.",
                'danger'
            );
        });
    }

    public function verify(Reimbursement $r, User $actor, array $checklist): void
    {
        DB::transaction(function () use ($r, $actor, $checklist) {
            $r->lockForUpdate()->find($r->id);
            $from = $r->status->value;

            $r->update([
                'status'      => ReimbursementStatus::Verified,
                'verified_at' => now(),
            ]);

            $this->logger->log($r, $actor, 'verify', $from, ReimbursementStatus::Verified->value, null, [
                'checklist' => $checklist,
            ]);

            $this->notifier->notifyOwner(
                $r, $actor,
                "Pengajuan Terverifikasi: {$r->code}",
                "Pengajuan \"{$r->title}\" Anda telah diverifikasi dan menunggu pembayaran.",
                'success'
            );

            $this->notifier->notifyCashierTeam(
                $r, $actor,
                "Pengajuan Siap Dibayar: {$r->code}",
                "Pengajuan \"{$r->title}\" dari {$r->user->name} siap dibayar.",
                'info'
            );
        });
    }

    public function returnByFinance(Reimbursement $r, User $actor, string $note): void
    {
        DB::transaction(function () use ($r, $actor, $note) {
            $r->lockForUpdate()->find($r->id);
            $from = $r->status->value;

            $r->update([
                'status'       => ReimbursementStatus::Revision,
                'revision_from'=> RevisionFrom::Finance,
                'verified_at'  => null,
                // approved_at tetap dipertahankan
            ]);

            $this->logger->log($r, $actor, 'return_by_finance', $from, ReimbursementStatus::Revision->value, $note);

            $this->notifier->notifyOwner(
                $r, $actor,
                "Perlu Revisi dari Finance: {$r->code}",
                "Pengajuan \"{$r->title}\" Anda diminta revisi oleh Finance.",
                'warning'
            );
        });
    }

    public function rejectByFinance(Reimbursement $r, User $actor, string $note): void
    {
        DB::transaction(function () use ($r, $actor, $note) {
            $r->lockForUpdate()->find($r->id);
            $from = $r->status->value;

            $r->update(['status' => ReimbursementStatus::Rejected]);

            $this->logger->log($r, $actor, 'reject_by_finance', $from, ReimbursementStatus::Rejected->value, $note);

            $this->notifier->notifyOwner(
                $r, $actor,
                "Pengajuan Ditolak oleh Finance: {$r->code}",
                "Pengajuan \"{$r->title}\" Anda ditolak oleh Finance.",
                'danger'
            );
        });
    }

    public function pay(Reimbursement $r, User $actor, array $data, UploadedFile $proof): void
    {
        DB::transaction(function () use ($r, $actor, $data, $proof) {
            $r->lockForUpdate()->find($r->id);

            // Upload bukti dulu — jika gagal, status tidak berubah
            $attachment = $this->reimbursementService->storeFile(
                $proof, $r, null, AttachmentKind::PaymentProof, $actor
            );

            $from = $r->status->value;

            $r->update([
                'status'            => ReimbursementStatus::Paid,
                'paid_at'           => $data['paid_at'],
                'payment_method'    => $data['payment_method'],
                'payment_reference' => $data['payment_reference'] ?? null,
                'payment_note'      => $data['payment_note'] ?? null,
            ]);

            $this->logger->log($r, $actor, 'pay', $from, ReimbursementStatus::Paid->value, $data['payment_note'] ?? null, [
                'payment_method'    => $data['payment_method'],
                'payment_reference' => $data['payment_reference'] ?? null,
                'attachment_id'     => $attachment->id,
            ]);

            $this->notifier->notifyOwner(
                $r, $actor,
                "Pengajuan Dibayar: {$r->code}",
                "Pengajuan \"{$r->title}\" Anda telah dibayar.",
                'success'
            );
        });
    }

    public function cancel(Reimbursement $r, User $actor, ?string $note): void
    {
        DB::transaction(function () use ($r, $actor, $note) {
            $r->lockForUpdate()->find($r->id);
            $from = $r->status->value;

            $wasSubmitted = $r->status === ReimbursementStatus::Submitted;

            $r->update(['status' => ReimbursementStatus::Cancelled]);

            $this->logger->log($r, $actor, 'cancel', $from, ReimbursementStatus::Cancelled->value, $note);

            if ($wasSubmitted) {
                $this->notifier->notifyManager(
                    $r, $actor,
                    "Pengajuan Dibatalkan: {$r->code}",
                    "{$actor->name} membatalkan pengajuan \"{$r->title}\".",
                    'warning'
                );
            }
        });
    }
}
