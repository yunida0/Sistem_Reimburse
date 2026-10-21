<?php

namespace App\Http\Controllers;

use App\Enums\ReimbursementStatus;
use App\Http\Requests\ApprovalActionRequest;
use App\Models\Reimbursement;
use App\Services\WorkflowService;

class ApprovalController extends Controller
{
    public function __construct(protected WorkflowService $workflow) {}

    public function index()
    {
        $user = auth()->user();
        $subordinateIds = $user->subordinates()->pluck('id');

        $reimbursements = Reimbursement::whereIn('user_id', $subordinateIds)
            ->where('status', ReimbursementStatus::Submitted)
            ->with('user.department')
            ->oldest('submitted_at')
            ->paginate(20)->withQueryString();

        return view('approvals.index', compact('reimbursements'));
    }

    public function approve(Reimbursement $reimbursement)
    {
        $this->authorize('approve', $reimbursement);
        $this->workflow->approve($reimbursement, auth()->user());
        return back()->with('success', 'Pengajuan berhasil disetujui.');
    }

    public function return(ApprovalActionRequest $request, Reimbursement $reimbursement)
    {
        $this->authorize('returnByApprover', $reimbursement);
        $this->workflow->returnByApprover($reimbursement, auth()->user(), $request->note);
        return back()->with('success', 'Pengajuan dikembalikan untuk revisi.');
    }

    public function reject(ApprovalActionRequest $request, Reimbursement $reimbursement)
    {
        $this->authorize('rejectByApprover', $reimbursement);
        $this->workflow->rejectByApprover($reimbursement, auth()->user(), $request->note);
        return back()->with('success', 'Pengajuan berhasil ditolak.');
    }
}
