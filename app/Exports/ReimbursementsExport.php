<?php

namespace App\Exports;

use App\Enums\ReimbursementStatus;
use App\Models\Reimbursement;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReimbursementsExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    public function __construct(protected array $filters = []) {}

    public function query()
    {
        $dateFrom = $this->filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo   = $this->filters['date_to']   ?? now()->endOfMonth()->toDateString();

        return Reimbursement::query()
            ->whereNotNull('submitted_at')
            ->where('status', '!=', ReimbursementStatus::Draft->value)
            ->whereBetween('submitted_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->when($this->filters['department_id'] ?? null, fn($q, $v) => $q->where('department_id', $v))
            ->when($this->filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->with(['user', 'department'])
            ->orderBy('submitted_at');
    }

    public function headings(): array
    {
        return [
            'Kode', 'Pegawai', 'Departemen', 'Judul',
            'Tanggal Submit', 'Total', 'Status',
            'Tanggal Bayar', 'Metode Pembayaran', 'Referensi',
        ];
    }

    public function map($row): array
    {
        return [
            $row->code ?? '-',
            $row->user->name,
            $row->department->name ?? '-',
            $row->title,
            $row->submitted_at?->format('d/m/Y'),
            $row->total_amount,
            $row->status->label(),
            $row->paid_at?->format('d/m/Y'),
            $row->payment_method?->label(),
            $row->payment_reference ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
