@extends('layouts.app')
@section('title', 'Antrean Persetujuan')
@section('page-title', 'Antrean Persetujuan')

@section('content')
<div class="py-4">
    <x-card>
        @if($reimbursements->isEmpty())
            <x-empty-state icon="check-circle" title="Tidak ada pengajuan yang menunggu persetujuan"
                           description="Semua pengajuan bawahan sudah diproses."/>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Kode</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Pegawai</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Judul</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Tgl Submit</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Total</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Aging</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($reimbursements as $r)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-3 font-mono text-sm text-slate-700">{{ $r->code }}</td>
                        <td class="px-5 py-3">
                            <p class="font-medium text-slate-800">{{ $r->user->name }}</p>
                            <p class="text-xs text-slate-400">{{ $r->user->department?->name }}</p>
                        </td>
                        <td class="px-5 py-3 text-slate-700">{{ Str::limit($r->title, 45) }}</td>
                        <td class="px-5 py-3 text-slate-500 whitespace-nowrap">{{ $r->submitted_at?->format('d M Y') }}</td>
                        <td class="px-5 py-3 text-right font-medium text-slate-700 whitespace-nowrap">
                            <x-money :amount="$r->total_amount"/>
                        </td>
                        <td class="px-5 py-3"><x-aging-badge :days="$r->agingDays()"/></td>
                        <td class="px-5 py-3">
                            <a href="{{ route('reimbursements.show', $r) }}" class="btn-primary text-xs">Periksa</a>
                        </td>
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
@endsection
