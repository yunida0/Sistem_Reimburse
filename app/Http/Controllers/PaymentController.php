<?php

namespace App\Http\Controllers;

use App\Enums\ReimbursementStatus;
use App\Http\Requests\PaymentRequest;
use App\Models\Reimbursement;
use App\Services\WorkflowService;

class PaymentController extends Controller
{
    public function __construct(protected WorkflowService $workflow) {}

    public function index()
    {
        $reimbursements = Reimbursement::where('status', ReimbursementStatus::Verified)
            ->with('user.department')
            ->oldest('verified_at')
            ->paginate(20)->withQueryString();

        return view('payments.index', compact('reimbursements'));
    }

    public function create(Reimbursement $reimbursement)
    {
        $this->authorize('pay', $reimbursement);
        $reimbursement->load('user.department', 'items.category');

        return view('payments.create', compact('reimbursement'));
    }

    public function store(PaymentRequest $request, Reimbursement $reimbursement)
    {
        $this->authorize('pay', $reimbursement);

        $this->workflow->pay(
            $reimbursement,
            auth()->user(),
            $request->validated(),
            $request->file('proof')
        );

        return redirect()->route('reimbursements.show', $reimbursement)
            ->with('success', 'Pembayaran berhasil dicatat.');
    }
}
