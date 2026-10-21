@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@php $user = auth()->user(); @endphp

<div class="py-4 space-y-6">

    {{-- Sapaan --}}
    <div>
        <h2 class="text-xl font-semibold text-slate-800">Selamat datang, {{ $user->name }} 👋</h2>
        <p class="text-sm text-slate-500 mt-0.5">{{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}</p>
    </div>

    {{-- ── Kartu ringkasan pengajuan sendiri ──────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <x-card class="p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Draf</p>
            <p class="text-3xl font-bold text-slate-800">{{ $myStats['draft'] }}</p>
        </x-card>
        <x-card class="p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Menunggu</p>
            <p class="text-3xl font-bold text-blue-600">{{ $myStats['submitted'] }}</p>
        </x-card>
        <x-card class="p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Perlu Revisi</p>
            <p class="text-3xl font-bold text-amber-500">{{ $myStats['revision'] }}</p>
        </x-card>
        <x-card class="p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Dibayar Bulan Ini</p>
            <p class="text-xl font-bold text-emerald-600"><x-money :amount="$myStats['paid_month']"/></p>
        </x-card>
    </div>

    {{-- ── Pengajuan terbaru ──────────────────────────────────────────── --}}
    <x-card>
        <div class="flex items-center justify-between p-5 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Pengajuan Terbaru</h3>
            <a href="{{ route('reimbursements.create') }}" class="btn-primary text-sm">+ Buat Pengajuan</a>
        </div>
        @if($myLatest->isEmpty())
            <x-empty-state icon="document-text" title="Belum ada pengajuan" description="Buat pengajuan reimbursement pertama Anda.">
                <a href="{{ route('reimbursements.create') }}" class="btn-primary">Buat Pengajuan</a>
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Kode</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Judul</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Total</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($myLatest as $r)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-3">
                                <a href="{{ route('reimbursements.show', $r) }}" class="font-medium text-blue-600 hover:text-blue-800">
                                    {{ $r->code ?? 'DRAF' }}
                                </a>
                            </td>
                            <td class="px-5 py-3 text-slate-700">{{ Str::limit($r->title, 40) }}</td>
                            <td class="px-5 py-3 text-slate-700 font-medium"><x-money :amount="$r->total_amount"/></td>
                            <td class="px-5 py-3"><x-status-badge :status="$r->status"/></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-slate-100">
                <a href="{{ route('reimbursements.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                    Lihat semua pengajuan →
                </a>
            </div>
        @endif
    </x-card>

    {{-- ── Atasan: Antrean Persetujuan ─────────────────────────────────── --}}
    @if($approvalStats)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <x-card class="p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Menunggu Persetujuan</p>
            <p class="text-3xl font-bold text-blue-600">{{ $approvalStats['pending'] }}</p>
        </x-card>
        <x-card class="p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Total Dibayar Tim (Bulan Ini)</p>
            <p class="text-xl font-bold text-emerald-600"><x-money :amount="$approvalStats['team_paid_month']"/></p>
        </x-card>
    </div>

    @if($approvalQueue->isNotEmpty())
    <x-card>
        <div class="flex items-center justify-between p-5 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Menunggu Persetujuan Anda</h3>
            <a href="{{ route('approvals.index') }}" class="text-sm text-blue-600 hover:text-blue-800">Lihat semua</a>
        </div>
        <div class="divide-y divide-slate-50">
            @foreach($approvalQueue as $r)
            <div class="flex items-center gap-4 px-5 py-3">
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-slate-800 truncate">{{ $r->title }}</p>
                    <p class="text-xs text-slate-500">{{ $r->user->name }} · {{ $r->submitted_at?->diffForHumans() }}</p>
                </div>
                <x-aging-badge :days="$r->agingDays()"/>
                <x-money :amount="$r->total_amount" class="text-sm font-semibold text-slate-700"/>
                <a href="{{ route('reimbursements.show', $r) }}" class="btn-secondary text-xs">Periksa</a>
            </div>
            @endforeach
        </div>
    </x-card>
    @endif
    @endif

    {{-- ── Finance ──────────────────────────────────────────────────────── --}}
    @if($financeStats)
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <x-card class="p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Menunggu Verifikasi</p>
            <p class="text-3xl font-bold text-indigo-600">{{ $financeStats['waiting_verify'] }}</p>
        </x-card>
        <x-card class="p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Menunggu Pembayaran</p>
            <p class="text-3xl font-bold text-cyan-600">{{ $financeStats['waiting_payment'] }}</p>
        </x-card>
        <x-card class="p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Dibayar Bulan Ini</p>
            <p class="text-xl font-bold text-emerald-600"><x-money :amount="$financeStats['paid_month']"/></p>
        </x-card>
    </div>
    @endif

    {{-- ── Kasir ────────────────────────────────────────────────────────── --}}
    @if($cashierStats)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <x-card class="p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Siap Dibayar</p>
            <p class="text-3xl font-bold text-cyan-600">{{ $cashierStats['ready'] }}</p>
        </x-card>
        <x-card class="p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Dibayar Hari Ini</p>
            <p class="text-3xl font-bold text-emerald-600">{{ $cashierStats['paid_today'] }}</p>
        </x-card>
    </div>

    @if($cashierQueue->isNotEmpty())
    <x-card>
        <div class="p-5 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Antrean Pembayaran</h3>
        </div>
        <div class="divide-y divide-slate-50">
            @foreach($cashierQueue as $r)
            <div class="flex items-center gap-4 px-5 py-3">
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-slate-800 truncate">{{ $r->title }}</p>
                    <p class="text-xs text-slate-500">{{ $r->user->name }}</p>
                </div>
                <x-aging-badge :days="$r->agingDays()"/>
                <x-money :amount="$r->total_amount" class="text-sm font-semibold"/>
                <a href="{{ route('payments.create', $r) }}" class="btn-primary text-xs">Bayar</a>
            </div>
            @endforeach
        </div>
    </x-card>
    @endif
    @endif

    {{-- ── Admin ─────────────────────────────────────────────────────────── --}}
    @if($adminStats)
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <x-card class="p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Total Pengajuan</p>
            <p class="text-3xl font-bold text-slate-800">{{ $adminStats['total_reimbursements'] }}</p>
        </x-card>
        <x-card class="p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Dibayar Bulan Ini</p>
            <p class="text-xl font-bold text-emerald-600"><x-money :amount="$adminStats['paid_month']"/></p>
        </x-card>
        <x-card class="p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Pengguna Aktif</p>
            <p class="text-3xl font-bold text-slate-800">{{ $adminStats['active_users'] }}</p>
        </x-card>
    </div>

    @if($trendData)
    <x-card class="p-5">
        <h3 class="font-semibold text-slate-800 mb-4">Tren 6 Bulan Terakhir</h3>
        <canvas id="trendChart" height="80"></canvas>
    </x-card>

    @push('scripts')
    <script>
    const ctx = document.getElementById('trendChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json(array_column($trendData, 'label')),
            datasets: [
                {
                    label: 'Diajukan',
                    data: @json(array_column($trendData, 'submitted')),
                    backgroundColor: 'rgba(99,102,241,0.7)',
                    borderRadius: 4,
                },
                {
                    label: 'Dibayar',
                    data: @json(array_column($trendData, 'paid')),
                    backgroundColor: 'rgba(16,185,129,0.7)',
                    borderRadius: 4,
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: (ctx) => ' Rp' + ctx.raw.toLocaleString('id-ID')
                    }
                }
            },
            scales: {
                y: {
                    ticks: {
                        callback: (val) => 'Rp' + (val/1000000).toFixed(1) + 'jt'
                    }
                }
            }
        }
    });
    </script>
    @endpush
    @endif
    @endif

</div>
@endsection
