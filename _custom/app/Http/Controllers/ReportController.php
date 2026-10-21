<?php

namespace App\Http\Controllers;

use App\Enums\ReimbursementStatus;
use App\Exports\ReimbursementsExport;
use App\Models\Department;
use App\Models\Reimbursement;
use App\Models\ReimbursementItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $defaultFrom = now()->startOfMonth()->toDateString();
        $defaultTo   = now()->endOfMonth()->toDateString();

        $dateFrom = $request->get('date_from', $defaultFrom);
        $dateTo   = $request->get('date_to', $defaultTo);
        $deptId   = $request->get('department_id');
        $status   = $request->get('status');

        $query = Reimbursement::query()
            ->whereNotNull('submitted_at')
            ->where('status', '!=', ReimbursementStatus::Draft->value)
            ->whereBetween('submitted_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->with(['user', 'department', 'items.category']);

        if ($deptId) {
            $query->where('department_id', $deptId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $reimbursements = $query->latest('submitted_at')->paginate(20)->withQueryString();

        // Summary
        $baseQuery = clone $query->getQuery();
        $summary = Reimbursement::whereNotNull('submitted_at')
            ->where('status', '!=', ReimbursementStatus::Draft->value)
            ->whereBetween('submitted_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->when($deptId, fn($q) => $q->where('department_id', $deptId))
            ->when($status, fn($q) => $q->where('status', $status));

        $summaryData = [
            'count'          => (clone $summary)->count(),
            'total_amount'   => (clone $summary)->sum('total_amount'),
            'total_paid'     => (clone $summary)->where('status', ReimbursementStatus::Paid->value)->sum('total_amount'),
            'avg_days'       => (clone $summary)->where('status', ReimbursementStatus::Paid->value)
                                    ->whereNotNull('paid_at')
                                    ->selectRaw('AVG(DATEDIFF(paid_at, submitted_at)) as avg_days')
                                    ->value('avg_days'),
        ];

        // Rekap per departemen
        $deptSummary = Reimbursement::whereNotNull('submitted_at')
            ->where('status', '!=', ReimbursementStatus::Draft->value)
            ->whereBetween('submitted_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->select('department_id',
                DB::raw('COUNT(*) as total_count'),
                DB::raw('SUM(total_amount) as total_amount'),
                DB::raw('SUM(CASE WHEN status = "PAID" THEN total_amount ELSE 0 END) as total_paid')
            )
            ->groupBy('department_id')
            ->with('department')
            ->get();

        // Rekap per kategori
        $categorySummary = ReimbursementItem::select(
                'category_id',
                DB::raw('COUNT(*) as total_count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy('category_id')
            ->with('category')
            ->get();

        // Tren 6 bulan
        $trendData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $trendData[] = [
                'label'    => $month->locale('id')->isoFormat('MMM YY'),
                'submitted'=> Reimbursement::whereMonth('submitted_at', $month->month)
                                ->whereYear('submitted_at', $month->year)
                                ->whereNotNull('submitted_at')
                                ->sum('total_amount'),
                'paid'     => Reimbursement::where('status', ReimbursementStatus::Paid->value)
                                ->whereMonth('paid_at', $month->month)
                                ->whereYear('paid_at', $month->year)
                                ->sum('total_amount'),
            ];
        }

        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $statuses    = ReimbursementStatus::cases();

        return view('reports.index', compact(
            'reimbursements', 'summaryData', 'deptSummary', 'categorySummary',
            'trendData', 'departments', 'statuses',
            'dateFrom', 'dateTo', 'deptId', 'status'
        ));
    }

    public function export(Request $request)
    {
        $filename = 'laporan-reimbust-' . now()->format('Ymd');
        $format   = $request->get('format', 'xlsx');

        if ($format === 'csv') {
            return Excel::download(new ReimbursementsExport($request->all()), $filename . '.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        return Excel::download(new ReimbursementsExport($request->all()), $filename . '.xlsx');
    }
}
