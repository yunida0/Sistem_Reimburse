<?php

namespace App\Http\Controllers;

use App\Enums\ReimbursementStatus;
use App\Enums\UserRole;
use App\Models\Reimbursement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Stats pengajuan sendiri
        $myStats = [
            'draft'      => Reimbursement::where('user_id', $user->id)->where('status', ReimbursementStatus::Draft)->count(),
            'submitted'  => Reimbursement::where('user_id', $user->id)->where('status', ReimbursementStatus::Submitted)->count(),
            'revision'   => Reimbursement::where('user_id', $user->id)->where('status', ReimbursementStatus::Revision)->count(),
            'paid_month' => Reimbursement::where('user_id', $user->id)
                                ->where('status', ReimbursementStatus::Paid)
                                ->whereMonth('paid_at', now()->month)
                                ->whereYear('paid_at', now()->year)
                                ->sum('total_amount'),
        ];

        $myLatest = Reimbursement::where('user_id', $user->id)
                        ->with(['department'])
                        ->latest()
                        ->limit(5)
                        ->get();

        // Role-specific data
        $approvalQueue     = collect();
        $approvalStats     = null;
        $financeStats      = null;
        $financeQueue      = collect();
        $cashierStats      = null;
        $cashierQueue      = collect();
        $adminStats        = null;
        $trendData         = null;

        // Atasan
        if ($user->hasSubordinates()) {
            $subordinateIds = $user->subordinates()->pluck('id');

            $approvalStats = [
                'pending' => Reimbursement::whereIn('user_id', $subordinateIds)
                                ->where('status', ReimbursementStatus::Submitted)
                                ->count(),
                'team_paid_month' => Reimbursement::whereIn('user_id', $subordinateIds)
                                ->where('status', ReimbursementStatus::Paid)
                                ->whereMonth('paid_at', now()->month)
                                ->whereYear('paid_at', now()->year)
                                ->sum('total_amount'),
            ];

            $approvalQueue = Reimbursement::whereIn('user_id', $subordinateIds)
                ->where('status', ReimbursementStatus::Submitted)
                ->with('user')
                ->oldest('submitted_at')
                ->limit(5)
                ->get();
        }

        // Finance
        if ($user->hasRole(UserRole::Finance)) {
            $financeStats = [
                'waiting_verify'  => Reimbursement::where('status', ReimbursementStatus::Approved)->count(),
                'waiting_payment' => Reimbursement::where('status', ReimbursementStatus::Verified)->count(),
                'paid_month'      => Reimbursement::where('status', ReimbursementStatus::Paid)
                                        ->whereMonth('paid_at', now()->month)
                                        ->whereYear('paid_at', now()->year)
                                        ->sum('total_amount'),
            ];

            $financeQueue = Reimbursement::where('status', ReimbursementStatus::Approved)
                ->with('user')
                ->oldest('submitted_at')
                ->limit(5)
                ->get();
        }

        // Kasir
        if ($user->hasRole(UserRole::Cashier)) {
            $cashierStats = [
                'ready'     => Reimbursement::where('status', ReimbursementStatus::Verified)->count(),
                'paid_today'=> Reimbursement::where('status', ReimbursementStatus::Paid)
                                    ->whereDate('paid_at', today())
                                    ->count(),
            ];

            $cashierQueue = Reimbursement::where('status', ReimbursementStatus::Verified)
                ->with('user')
                ->oldest('verified_at')
                ->limit(5)
                ->get();
        }

        // Admin
        if ($user->hasRole(UserRole::Admin)) {
            $adminStats = [
                'total_reimbursements' => Reimbursement::count(),
                'paid_month'           => Reimbursement::where('status', ReimbursementStatus::Paid)
                                            ->whereMonth('paid_at', now()->month)
                                            ->whereYear('paid_at', now()->year)
                                            ->sum('total_amount'),
                'active_users'         => User::where('is_active', true)->count(),
            ];

            // Tren 6 bulan
            $trendData = $this->getTrendData();
        }

        return view('dashboard', compact(
            'myStats', 'myLatest',
            'approvalStats', 'approvalQueue',
            'financeStats', 'financeQueue',
            'cashierStats', 'cashierQueue',
            'adminStats', 'trendData'
        ));
    }

    private function getTrendData(): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $months[] = [
                'label'    => $month->locale('id')->isoFormat('MMM YY'),
                'submitted'=> Reimbursement::whereMonth('submitted_at', $month->month)
                                ->whereYear('submitted_at', $month->year)
                                ->whereNotNull('submitted_at')
                                ->sum('total_amount'),
                'paid'     => Reimbursement::where('status', ReimbursementStatus::Paid)
                                ->whereMonth('paid_at', $month->month)
                                ->whereYear('paid_at', $month->year)
                                ->sum('total_amount'),
            ];
        }
        return $months;
    }
}
