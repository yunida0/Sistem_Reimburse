@extends('layouts.app')

@section('title', 'Daftar Pengajuan')
@section('page-title', 'Pengajuan')

@section('content')
<div class="py-4 space-y-4">

    {{-- Header --}}
    <div class="flex flex-wrap items-center gap-3 justify-between">
        <div></div>
        <a href="{{ route('reimbursements.create') }}" class="btn-primary">
            + Buat Pengajuan
        </a>
    </div>

    {{-- Filters --}}
    <x-card class="p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Kode, judul, atau nama..."
                       class="input-field">
            </div>
            <div class="min-w-[180px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                <select name="status" class="input-field">
                    <option value="">Semua Status</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>
                            {{ $s->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Dari</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="input-field">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Sampai</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="input-field">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary">Filter</button>
                <a href="{{ route('reimbursements.index') }}" class="btn-secondary">Reset</a>
            </div>
        </form>
    </x-card>

    {{-- Tabel --}}
    <x-card>
        @if($reimbursements->isEmpty())
            <x-empty-state icon="document-text" title="Tidak ada pengajuan"
                           description="Belum ada pengajuan yang sesuai filter.">
                <a href="{{ route('reimbursements.create') }}" class="btn-primary">Buat Pengajuan</a>
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50">
                            <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Kode</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Pegawai</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Judul</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Tgl Submit</th>
                            <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Total</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Aging</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($reimbursements as $r)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-3 font-medium">
                                <a href="{{ route('reimbursements.show', $r) }}" class="text-blue-600 hover:text-blue-800">
                                    {{ $r->code ?? '—' }}
                                </a>
                            </td>
                            <td class="px-5 py-3 text-slate-700">{{ $r->user->name }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ Str::limit($r->title, 45) }}</td>
                            <td class="px-5 py-3 text-slate-500 whitespace-nowrap">
                                {{ $r->submitted_at?->format('d M Y') ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-right font-medium text-slate-700 whitespace-nowrap">
                                <x-money :amount="$r->total_amount"/>
                            </td>
                            <td class="px-5 py-3"><x-status-badge :status="$r->status"/></td>
                            <td class="px-5 py-3">
                                @if($r->status->showAging())
                                    <x-aging-badge :days="$r->agingDays()"/>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <a href="{{ route('reimbursements.show', $r) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium whitespace-nowrap">
                                    Detail
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-between gap-4">
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <span>Tampilkan</span>
                    <form method="GET">
                        <input type="hidden" name="status" value="{{ request('status') }}">
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        <select name="per_page" onchange="this.form.submit()" class="border border-slate-200 rounded-lg px-2 py-1 text-sm">
                            @foreach([10, 20, 50] as $pp)
                                <option value="{{ $pp }}" {{ request('per_page', 10) == $pp ? 'selected' : '' }}>{{ $pp }}</option>
                            @endforeach
                        </select>
                    </form>
                    <span>data</span>
                </div>
                {{ $reimbursements->links() }}
            </div>
        @endif
    </x-card>
</div>
@endsection
