<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Models\Reimbursement;
use App\Services\NotificationService;

class CommentController extends Controller
{
    public function __construct(protected NotificationService $notifier) {}

    public function store(CommentRequest $request, Reimbursement $reimbursement)
    {
        $this->authorize('comment', $reimbursement);

        $comment = $reimbursement->comments()->create([
            'user_id' => auth()->id(),
            'body'    => $request->body,
        ]);

        // Notifikasi ke pemilik jika bukan pemilik yang komentar
        $user = auth()->user();
        if ($user->id !== $reimbursement->user_id) {
            $reimbursement->user->notify(new \App\Notifications\ReimbursementNotification(
                "Komentar Baru: {$reimbursement->code}",
                "{$user->name} mengomentari pengajuan \"{$reimbursement->title}\".",
                $reimbursement,
                'info'
            ));
        }

        return back()->with('success', 'Komentar berhasil ditambahkan.');
    }
}
