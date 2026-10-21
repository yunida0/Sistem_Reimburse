<?php

namespace App\Http\Controllers;

use App\Enums\ReimbursementStatus;
use App\Http\Requests\FinanceActionRequest;
use App\Models\Reimbursement;
use App\Services\WorkflowService;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function __construct(protected WorkflowService $workflow) {}

    public function index()
    {
        $reimbursements = Reimbursement::where('status', ReimbursementStatus::Approved)
            ->with('user.department')
            ->oldest('submitted_at')
            ->paginate(20)->withQueryString();

        return view('finance.index', compact('reimbursements'));
    }

    public function verify(Request $request, Reimbursement $reimbursement)
    {
        $this->authorize('verify', $reimbursement);

        $request->validate([
            'checklist' => ['required', 'array', 'size:3'],
            'checklist.*' => ['accepted'],
        ], [
            'checklist.required' => 'Semua checklist verifikasi harus dicentang.',
            'checklist.size'     => 'Semua checklist verifikasi harus dicentang.',
            'checklist.*.accepted'=> 'Semua checklist verifikasi harus dicentang.',
        ]);

        $this->workflow->verify($reimbursement, auth()->user(), $request->checklist ?? []);

        return redirect()->route('finance.index')->with('success', 'Pengajuan berhasil diverifikasi.');
    }

    public function return(FinanceActionRequest $request, Reimbursement $reimbursement)
    {
        $this->authorize('returnByFinance', $reimbursement);
        $this->workflow->returnByFinance($reimbursement, auth()->user(), $request->note);
        return back()->with('success', 'Pengajuan dikembalikan untuk revisi.');
    }

    public function reject(FinanceActionRequest $request, Reimbursement $reimbursement)
    {
        $this->authorize('rejectByFinance', $reimbursement);
        $this->workflow->rejectByFinance($reimbursement, auth()->user(), $request->note);
        return back()->with('success', 'Pengajuan berhasil ditolak.');
    }
}
