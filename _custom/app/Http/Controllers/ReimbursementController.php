<?php

namespace App\Http\Controllers;

use App\Enums\ReimbursementStatus;
use App\Enums\UserRole;
use App\Http\Requests\StoreReimbursementRequest;
use App\Http\Requests\UpdateReimbursementRequest;
use App\Models\Category;
use App\Models\Reimbursement;
use App\Services\ReimbursementService;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReimbursementController extends Controller
{
    public function __construct(
        protected ReimbursementService $reimbursementService,
        protected WorkflowService $workflowService,
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Reimbursement::with(['user', 'department'])
                    ->latest('created_at');

        // Scope berdasarkan role
        if ($user->hasRole(UserRole::Employee)) {
            $query->where('user_id', $user->id);
        } elseif ($user->hasRole(UserRole::Approver)) {
            $subordinateIds = $user->subordinates()->pluck('id')->push($user->id);
            $query->whereIn('user_id', $subordinateIds);
        } elseif ($user->hasRole(UserRole::Cashier)) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereIn('status', [ReimbursementStatus::Verified->value, ReimbursementStatus::Paid->value]);
            });
        }
        // Finance & Admin: lihat semua

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%$search%")
                  ->orWhere('title', 'like', "%$search%")
                  ->orWhereHas('user', fn($q) => $q->where('name', 'like', "%$search%"));
            });
        }

        if ($request->filled('date_from')) {
            $query->where('submitted_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('submitted_at', '<=', $request->date_to . ' 23:59:59');
        }

        $perPage = in_array($request->per_page, [10, 20, 50]) ? $request->per_page : 10;
        $reimbursements = $query->paginate($perPage)->withQueryString();

        $statuses = ReimbursementStatus::cases();

        return view('reimbursements.index', compact('reimbursements', 'statuses'));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        return view('reimbursements.create', compact('categories'));
    }

    public function store(StoreReimbursementRequest $request)
    {
        $user = auth()->user();

        if (!$user->department_id) {
            return back()->withErrors(['general' => 'Akun Anda belum memiliki departemen. Hubungi Admin.']);
        }

        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $user, &$reimbursement, &$storedPaths) {
                $reimbursement = $this->reimbursementService->createDraft($user, $request->validated());

                if ($request->has('items')) {
                    $this->reimbursementService->syncItems($reimbursement, $request->input('items', []));
                    $storedPaths = $this->reimbursementService->storeUploadedReceipts($reimbursement, $request->file('items', []), $user);
                    $this->reimbursementService->recalculateTotal($reimbursement);
                }

                if ($request->input('action') === 'submit') {
                    $this->workflowService->submit($reimbursement, $user);
                }
            });
        } catch (\Throwable $e) {
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            throw $e;
        }

        $message = $request->input('action') === 'submit'
            ? 'Pengajuan berhasil diajukan.'
            : 'Draf pengajuan berhasil disimpan.';

        return redirect()->route('reimbursements.show', $reimbursement)->with('success', $message);
    }

    public function show(Reimbursement $reimbursement)
    {
        $this->authorize('view', $reimbursement);

        $reimbursement->load([
            'user.department', 'department',
            'items.category', 'items.attachments',
            'attachments',
            'logs.actor',
            'comments.user',
            'paymentProofs',
        ]);

        return view('reimbursements.show', compact('reimbursement'));
    }

    public function edit(Reimbursement $reimbursement)
    {
        $this->authorize('update', $reimbursement);

        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $reimbursement->load('items.attachments', 'items.category');

        return view('reimbursements.edit', compact('reimbursement', 'categories'));
    }

    public function update(UpdateReimbursementRequest $request, Reimbursement $reimbursement)
    {
        $this->authorize('update', $reimbursement);

        $user = auth()->user();

        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $reimbursement, $user, &$storedPaths) {
                $this->reimbursementService->updateDraft($reimbursement, $request->validated());

                if ($request->has('items')) {
                    $this->reimbursementService->syncItems($reimbursement, $request->input('items', []));
                    $storedPaths = $this->reimbursementService->storeUploadedReceipts($reimbursement, $request->file('items', []), $user);
                    $this->reimbursementService->recalculateTotal($reimbursement);
                }

                if ($request->input('action') === 'submit') {
                    $this->workflowService->submit($reimbursement, $user);
                }
            });
        } catch (\Throwable $e) {
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            throw $e;
        }

        $message = $request->input('action') === 'submit'
            ? 'Pengajuan berhasil diajukan.'
            : 'Draf berhasil diperbarui.';

        return redirect()->route('reimbursements.show', $reimbursement)->with('success', $message);
    }

    public function submit(Reimbursement $reimbursement)
    {
        $this->authorize('submit', $reimbursement);

        $this->workflowService->submit($reimbursement, auth()->user());

        return back()->with('success', 'Pengajuan berhasil diajukan.');
    }

    public function cancel(Request $request, Reimbursement $reimbursement)
    {
        $this->authorize('cancel', $reimbursement);

        $this->workflowService->cancel($reimbursement, auth()->user(), $request->note);

        return redirect()->route('reimbursements.index')->with('success', 'Pengajuan berhasil dibatalkan.');
    }
}
