@extends('layouts.app')
@section('title', 'Laporan')
@section('page-title', 'Laporan Reimbursement')

@section('content')
<div class="py-4 space-y-6">

    {{-- Filter --}}
    <x-card class="p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Dari</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="input-field">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Sampai</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="input-field">
            </div>
            <div class="min-w-[180px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Departemen</label>
                <select name="department_id" class="input-field">
                    <option value="">Semua Departemen</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ $deptId == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[180px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                <select name="status" class="input-field">
                    <option value="">Semua Status</option>
                    @foreach($statuses as $s)
                        @if($s !== \App\Enums\ReimbursementStatus::Draft)
                            <option value="{{ $s->value }}" {{ $status === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary">Filter</button>
                <a href="{{ route('reports.index') }}" class="btn-secondary">Reset</a>
            </div>
            <div class="ml-auto flex gap-2">
                <a href="{{ route('reports.export', array_merge(request()->all(), ['format' => 'xlsx'])) }}" class="btn-secondary text-sm">
                    Export Excel
                </a>
                <a href="{{ route('reports.export', array_merge(request()->all(), ['format' => 'csv'])) }}" class="btn-secondary text-sm">
                    Export CSV
                </a>
            </div>
        </form>
    </x-card>

    {{-- Kartu ringkasan --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <x-card class="p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Jumlah Pengajuan</p>
            <p class="text-3xl font-bold text-slate-800">{{ $summaryData['count'] }}</p>
        </x-card>
        <x-card class="p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Total Nominal</p>
            <p class="text-xl font-bold text-slate-800"><x-money :amount="$summaryData['total_amount']"/></p>
        </x-card>
        <x-card class="p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Total Dibayar</p>
            <p class="text-xl font-bold text-emerald-600"><x-money :amount="$summaryData['total_paid']"/></p>
        </x-card>
        <x-card class="p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Rata-rata Proses</p>
            <p class="text-2xl font-bold text-slate-800">{{ number_format($summaryData['avg_days'] ?? 0, 1) }} <span class="text-sm font-normal text-slate-500">hari</span></p>
        </x-card>
    </div>

    {{-- Grafik --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-card class="p-5">
            <h3 class="font-semibold text-slate-800 mb-4">Tren 6 Bulan</h3>
            <canvas id="trendChart" height="120"></canvas>
        </x-card>

        <x-card class="p-5">
            <h3 class="font-semibold text-slate-800 mb-4">Komposisi Kategori</h3>
            <canvas id="categoryChart" height="120"></canvas>
        </x-card>
    </div>

    {{-- Rekap per departemen --}}
    @if($deptSummary->isNotEmpty())
    <x-card>
        <div class="p-5 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Rekap per Departemen</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Departemen</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Jumlah</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Nominal</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Dibayar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($deptSummary as $row)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-medium text-slate-800">{{ $row->department?->name ?? 'Tidak Diketahui' }}</td>
                        <td class="px-5 py-3 text-right text-slate-700">{{ $row->total_count }}</td>
                        <td class="px-5 py-3 text-right text-slate-700"><x-money :amount="$row->total_amount"/></td>
                        <td class="px-5 py-3 text-right text-emerald-700 font-medium"><x-money :amount="$row->total_paid"/></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
    @endif

    {{-- Tabel rekap --}}
    <x-card>
        <div class="p-5 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Daftar Pengajuan</h3>
        </div>
        @if($reimbursements->isEmpty())
            <x-empty-state icon="chart-bar" title="Tidak ada data" description="Tidak ada data sesuai filter yang dipilih."/>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Kode</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Pegawai</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Departemen</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Judul</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Tgl Submit</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Total</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Tgl Bayar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($reimbursements as $r)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3"><a href="{{ route('reimbursements.show', $r) }}" class="text-blue-600 hover:text-blue-800 font-mono text-xs">{{ $r->code }}</a></td>
                        <td class="px-5 py-3 text-slate-700">{{ $r->user->name }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $r->department->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-slate-700">{{ Str::limit($r->title, 40) }}</td>
                        <td class="px-5 py-3 text-slate-500 whitespace-nowrap">{{ $r->submitted_at?->format('d M Y') }}</td>
                        <td class="px-5 py-3 text-right font-medium text-slate-700 whitespace-nowrap"><x-money :amount="$r->total_amount"/></td>
                        <td class="px-5 py-3"><x-status-badge :status="$r->status"/></td>
                        <td class="px-5 py-3 text-slate-500 whitespace-nowrap">{{ $r->paid_at?->format('d M Y') ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-slate-100">
            {{ $reimbursements->links() }}
        </div>
        @endif
    </x-card>
</div>

@push('scripts')
<script>
// Tren chart
new Chart(document.getElementById('trendChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: @json(array_column($trendData, 'label')),
        datasets: [
            { label: 'Diajukan', data: @json(array_column($trendData, 'submitted')), backgroundColor: 'rgba(99,102,241,0.7)', borderRadius: 4 },
            { label: 'Dibayar', data: @json(array_column($trendData, 'paid')), backgroundColor: 'rgba(16,185,129,0.7)', borderRadius: 4 }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' }, tooltip: { callbacks: { label: (c) => ' Rp' + c.raw.toLocaleString('id-ID') } } },
        scales: { y: { ticks: { callback: (v) => 'Rp' + (v/1000000).toFixed(1) + 'jt' } } }
    }
});

// Kategori doughnut
new Chart(document.getElementById('categoryChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: @json($categorySummary->pluck('category.name')),
        datasets: [{ data: @json($categorySummary->pluck('total_amount')), backgroundColor: ['#6366f1','#10b981','#f59e0b','#ef4444','#3b82f6'], borderWidth: 0 }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'right' },
            tooltip: { callbacks: { label: (c) => c.label + ': Rp' + c.raw.toLocaleString('id-ID') } }
        }
    }
});
</script>
@endpush
@endsection
