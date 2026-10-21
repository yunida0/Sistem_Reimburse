@extends('layouts.app')

@section('title', $reimbursement->code ?? 'Detail Pengajuan')
@section('page-title', 'Detail Pengajuan')

@section('content')
@php $user = auth()->user(); @endphp

<div class="py-4 space-y-6" x-data="{ previewUrl: '', previewType: '', showPreview: false }">

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <x-card class="p-6">
        <div class="flex flex-wrap items-start gap-4 justify-between">
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <h2 class="text-xl font-bold text-slate-800">{{ $reimbursement->title }}</h2>
                    <x-status-badge :status="$reimbursement->status"/>
                    @if($reimbursement->status->showAging())
                        <x-aging-badge :days="$reimbursement->agingDays()"/>
                    @endif
                </div>
                <p class="text-sm text-slate-500 mt-1">
                    <span class="font-mono text-slate-700">{{ $reimbursement->code ?? '—' }}</span>
                    &nbsp;·&nbsp; {{ $reimbursement->user->name }}
                    &nbsp;·&nbsp; {{ $reimbursement->department->name ?? '—' }}
                    &nbsp;·&nbsp; Dibuat {{ $reimbursement->created_at->format('d M Y') }}
                    @if($reimbursement->submitted_at)
                        &nbsp;·&nbsp; Diajukan {{ $reimbursement->submitted_at->format('d M Y') }}
                    @endif
                </p>
            </div>
            <div class="text-right">
                <p class="text-xs text-slate-500 uppercase tracking-wider">Total</p>
                <p class="text-2xl font-bold text-blue-700"><x-money :amount="$reimbursement->total_amount"/></p>
            </div>
        </div>

        @if($reimbursement->description)
            <p class="mt-4 text-sm text-slate-600 bg-slate-50 rounded-lg p-3">{{ $reimbursement->description }}</p>
        @endif

        {{-- Alert revisi --}}
        @if($reimbursement->status === \App\Enums\ReimbursementStatus::Revision)
        @php $lastReturn = $reimbursement->logs->where('to_status', 'REVISION')->last(); @endphp
        <div class="mt-4 bg-amber-50 border border-amber-200 rounded-xl p-4 flex gap-3">
            <x-icon name="exclamation-triangle" class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5"/>
            <div>
                <p class="font-semibold text-amber-800">Perlu Revisi dari {{ $reimbursement->revision_from?->label() }}</p>
                @if($lastReturn?->note)
                    <p class="text-sm text-amber-700 mt-1">{{ $lastReturn->note }}</p>
                @endif
            </div>
        </div>
        @endif

        {{-- Tombol aksi --}}
        <div class="mt-5 flex flex-wrap gap-2">
            {{-- Owner actions --}}
            @can('update', $reimbursement)
                <a href="{{ route('reimbursements.edit', $reimbursement) }}" class="btn-secondary">Edit</a>
            @endcan

            @can('submit', $reimbursement)
                <form action="{{ route('reimbursements.submit', $reimbursement) }}" method="POST">
                    @csrf
                    <button class="btn-primary">Ajukan</button>
                </form>
            @endcan

            @can('cancel', $reimbursement)
                <div x-data="{ open: false }">
                    <button @click="open = true" class="btn-danger-outline">Batalkan</button>
                    <div x-show="open" x-transition class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display:none">
                        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
                        <div class="relative bg-white rounded-2xl p-6 max-w-md w-full shadow-xl">
                            <h3 class="font-semibold text-slate-800 mb-3">Batalkan Pengajuan?</h3>
                            <form action="{{ route('reimbursements.cancel', $reimbursement) }}" method="POST">
                                @csrf
                                <textarea name="note" rows="3" placeholder="Alasan pembatalan (opsional)..."
                                          class="input-field mb-4"></textarea>
                                <div class="flex gap-2 justify-end">
                                    <button type="button" @click="open = false" class="btn-secondary">Batal</button>
                                    <button type="submit" class="btn-danger">Ya, Batalkan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan

            {{-- Atasan --}}
            @can('approve', $reimbursement)
                <div x-data="{ openApprove: false }">
                    <button @click="openApprove = true" class="btn-success">Setujui</button>
                    <div x-show="openApprove" x-transition class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display:none">
                        <div class="absolute inset-0 bg-black/50" @click="openApprove = false"></div>
                        <div class="relative bg-white rounded-2xl p-6 max-w-sm w-full shadow-xl">
                            <h3 class="font-semibold text-slate-800 mb-2">Setujui Pengajuan?</h3>
                            <p class="text-sm text-slate-500 mb-4">Total: <x-money :amount="$reimbursement->total_amount"/></p>
                            <form action="{{ route('approvals.approve', $reimbursement) }}" method="POST">
                                @csrf
                                <div class="flex gap-2 justify-end">
                                    <button type="button" @click="openApprove = false" class="btn-secondary">Batal</button>
                                    <button type="submit" class="btn-success">Ya, Setujui</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan

            @can('returnByApprover', $reimbursement)
                <div x-data="{ open: false }">
                    <button @click="open = true" class="btn-warning">Minta Revisi</button>
                    <div x-show="open" x-transition class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display:none">
                        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
                        <div class="relative bg-white rounded-2xl p-6 max-w-md w-full shadow-xl">
                            <h3 class="font-semibold text-slate-800 mb-3">Minta Revisi</h3>
                            <form action="{{ route('approvals.return', $reimbursement) }}" method="POST">
                                @csrf
                                <textarea name="note" rows="4" required placeholder="Catatan revisi (wajib)..."
                                          class="input-field mb-4"></textarea>
                                <div class="flex gap-2 justify-end">
                                    <button type="button" @click="open = false" class="btn-secondary">Batal</button>
                                    <button type="submit" class="btn-warning">Kirim Revisi</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan

            @can('rejectByApprover', $reimbursement)
                <div x-data="{ open: false }">
                    <button @click="open = true" class="btn-danger">Tolak</button>
                    <div x-show="open" x-transition class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display:none">
                        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
                        <div class="relative bg-white rounded-2xl p-6 max-w-md w-full shadow-xl">
                            <h3 class="font-semibold text-slate-800 mb-3">Tolak Pengajuan</h3>
                            <form action="{{ route('approvals.reject', $reimbursement) }}" method="POST">
                                @csrf
                                <textarea name="note" rows="4" required placeholder="Alasan penolakan (wajib)..."
                                          class="input-field mb-4"></textarea>
                                <div class="flex gap-2 justify-end">
                                    <button type="button" @click="open = false" class="btn-secondary">Batal</button>
                                    <button type="submit" class="btn-danger">Tolak</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan

            {{-- Finance --}}
            @can('verify', $reimbursement)
                <div x-data="{ open: false }">
                    <button @click="open = true" class="btn-success">Verifikasi</button>
                    <div x-show="open" x-transition class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display:none">
                        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
                        <div class="relative bg-white rounded-2xl p-6 max-w-md w-full shadow-xl">
                            <h3 class="font-semibold text-slate-800 mb-4">Verifikasi Pengajuan</h3>
                            <form action="{{ route('finance.verify', $reimbursement) }}" method="POST">
                                @csrf
                                <div class="space-y-3 mb-5">
                                    <label class="flex items-start gap-3 cursor-pointer">
                                        <input type="checkbox" name="checklist[0]" value="1" required class="mt-0.5 w-4 h-4 text-blue-600 rounded">
                                        <span class="text-sm text-slate-700">Bukti transaksi lengkap dan valid</span>
                                    </label>
                                    <label class="flex items-start gap-3 cursor-pointer">
                                        <input type="checkbox" name="checklist[1]" value="1" required class="mt-0.5 w-4 h-4 text-blue-600 rounded">
                                        <span class="text-sm text-slate-700">Nominal sesuai dengan bukti</span>
                                    </label>
                                    <label class="flex items-start gap-3 cursor-pointer">
                                        <input type="checkbox" name="checklist[2]" value="1" required class="mt-0.5 w-4 h-4 text-blue-600 rounded">
                                        <span class="text-sm text-slate-700">Tanggal pengeluaran sesuai</span>
                                    </label>
                                </div>
                                <div class="flex gap-2 justify-end">
                                    <button type="button" @click="open = false" class="btn-secondary">Batal</button>
                                    <button type="submit" class="btn-success">Verifikasi</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan

            @can('returnByFinance', $reimbursement)
                <div x-data="{ open: false }">
                    <button @click="open = true" class="btn-warning">Minta Revisi</button>
                    <div x-show="open" x-transition class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display:none">
                        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
                        <div class="relative bg-white rounded-2xl p-6 max-w-md w-full shadow-xl">
                            <h3 class="font-semibold text-slate-800 mb-3">Minta Revisi</h3>
                            <form action="{{ route('finance.return', $reimbursement) }}" method="POST">
                                @csrf
                                <textarea name="note" rows="4" required placeholder="Catatan revisi (wajib)..."
                                          class="input-field mb-4"></textarea>
                                <div class="flex gap-2 justify-end">
                                    <button type="button" @click="open = false" class="btn-secondary">Batal</button>
                                    <button type="submit" class="btn-warning">Kirim Revisi</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan

            @can('rejectByFinance', $reimbursement)
                <div x-data="{ open: false }">
                    <button @click="open = true" class="btn-danger">Tolak</button>
                    <div x-show="open" x-transition class="fixed inset-0 z-50 flex items-center justify-center px-4" style="display:none">
                        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
                        <div class="relative bg-white rounded-2xl p-6 max-w-md w-full shadow-xl">
                            <h3 class="font-semibold text-slate-800 mb-3">Tolak Pengajuan</h3>
                            <form action="{{ route('finance.reject', $reimbursement) }}" method="POST">
                                @csrf
                                <textarea name="note" rows="4" required placeholder="Alasan penolakan (wajib)..."
                                          class="input-field mb-4"></textarea>
                                <div class="flex gap-2 justify-end">
                                    <button type="button" @click="open = false" class="btn-secondary">Batal</button>
                                    <button type="submit" class="btn-danger">Tolak</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan

            {{-- Kasir --}}
            @can('pay', $reimbursement)
                <a href="{{ route('payments.create', $reimbursement) }}" class="btn-success">Tandai Dibayar</a>
            @endcan
        </div>
    </x-card>

    {{-- ── Rincian Item ─────────────────────────────────────────────────── --}}
    <x-card>
        <div class="p-5 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Rincian Biaya</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Tanggal</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Kategori</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Deskripsi</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Vendor</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nominal</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Bukti</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($reimbursement->items as $item)
                    <tr>
                        <td class="px-5 py-3 whitespace-nowrap text-slate-600">{{ $item->expense_date->format('d M Y') }}</td>
                        <td class="px-5 py-3 text-slate-700">{{ $item->category->name }}</td>
                        <td class="px-5 py-3 text-slate-700">{{ $item->description }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $item->vendor ?? '—' }}</td>
                        <td class="px-5 py-3 text-right font-medium text-slate-700 whitespace-nowrap">
                            <x-money :amount="$item->amount"/>
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex flex-wrap gap-2">
                                @foreach($item->attachments as $att)
                                    <button type="button"
                                            @click="previewUrl = '{{ route('attachments.show', $att) }}'; previewType = '{{ $att->isImage() ? 'image' : 'pdf' }}'; showPreview = true"
                                            class="flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 transition-colors">
                                        <x-icon name="paper-clip" class="w-3.5 h-3.5"/>
                                        {{ Str::limit($att->original_name, 20) }}
                                    </button>
                                @endforeach
                                @if($item->attachments->isEmpty())
                                    <span class="text-slate-400 text-xs">—</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-slate-50 border-t border-slate-200">
                        <td colspan="4" class="px-5 py-3 font-semibold text-slate-700 text-right">Total</td>
                        <td class="px-5 py-3 text-right font-bold text-blue-700 text-base whitespace-nowrap">
                            <x-money :amount="$reimbursement->total_amount"/>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-card>

    {{-- ── Kartu Pembayaran ─────────────────────────────────────────────── --}}
    @if($reimbursement->status === \App\Enums\ReimbursementStatus::Paid)
    <x-card class="p-6">
        <h3 class="font-semibold text-slate-800 mb-4">Detail Pembayaran</h3>
        <div class="grid grid-cols-2 gap-4 text-sm mb-4">
            <div>
                <p class="text-slate-500 text-xs uppercase tracking-wider mb-1">Tanggal Bayar</p>
                <p class="font-medium text-slate-800">{{ $reimbursement->paid_at?->format('d M Y') }}</p>
            </div>
            <div>
                <p class="text-slate-500 text-xs uppercase tracking-wider mb-1">Metode</p>
                <p class="font-medium text-slate-800">{{ $reimbursement->payment_method?->label() }}</p>
            </div>
            @if($reimbursement->payment_reference)
            <div>
                <p class="text-slate-500 text-xs uppercase tracking-wider mb-1">Referensi</p>
                <p class="font-medium text-slate-800 font-mono">{{ $reimbursement->payment_reference }}</p>
            </div>
            @endif
            @if($reimbursement->payment_note)
            <div class="col-span-2">
                <p class="text-slate-500 text-xs uppercase tracking-wider mb-1">Catatan</p>
                <p class="text-slate-700">{{ $reimbursement->payment_note }}</p>
            </div>
            @endif
        </div>

        {{-- Bukti pembayaran --}}
        @if($reimbursement->paymentProofs->isNotEmpty())
        <div>
            <p class="text-xs uppercase tracking-wider text-slate-500 mb-2">Bukti Pembayaran</p>
            <div class="flex flex-wrap gap-2">
                @foreach($reimbursement->paymentProofs as $proof)
                <div class="flex items-center gap-2">
                    <button type="button"
                            @click="previewUrl = '{{ route('attachments.show', $proof) }}'; previewType = '{{ $proof->isImage() ? 'image' : 'pdf' }}'; showPreview = true"
                            class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 rounded-lg px-3 py-2 text-sm text-emerald-700 transition-colors">
                        <x-icon name="eye" class="w-4 h-4"/>
                        Lihat Bukti
                    </button>
                    <a href="{{ route('attachments.show', $proof) }}" download="{{ $proof->original_name }}"
                       class="flex items-center gap-2 bg-blue-50 border border-blue-200 hover:bg-blue-100 rounded-lg px-3 py-2 text-sm text-blue-700 transition-colors">
                        <x-icon name="download" class="w-4 h-4"/>
                        Unduh
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </x-card>
    @endif

    {{-- ── Timeline ─────────────────────────────────────────────────────── --}}
    <x-card class="p-6">
        <h3 class="font-semibold text-slate-800 mb-5">Riwayat Aktivitas</h3>
        @if($reimbursement->logs->isEmpty())
            <p class="text-sm text-slate-400">Belum ada aktivitas.</p>
        @else
        <div class="relative">
            <div class="absolute left-5 top-0 bottom-0 w-px bg-slate-200"></div>
            <div class="space-y-5">
                @foreach($reimbursement->logs as $log)
                <div class="flex gap-4 relative">
                    <div class="w-10 h-10 rounded-full bg-slate-100 border-2 border-white shadow flex items-center justify-center flex-shrink-0 z-10">
                        <x-icon name="clock" class="w-4 h-4 text-slate-500"/>
                    </div>
                    <div class="flex-1 pt-1.5 pb-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-medium text-slate-800 text-sm">{{ $log->actor?->name ?? 'Sistem' }}</span>
                            <span class="text-xs text-slate-500">{{ $log->created_at->format('d M Y H:i') }}</span>
                        </div>
                        <p class="text-sm text-slate-600 mt-0.5">
                            @php
                            $actionLabels = [
                                'submit'             => 'Mengajukan pengajuan',
                                'approve'            => 'Menyetujui pengajuan',
                                'return_by_approver' => 'Meminta revisi',
                                'reject_by_approver' => 'Menolak pengajuan',
                                'verify'             => 'Memverifikasi pengajuan',
                                'return_by_finance'  => 'Meminta revisi (Finance)',
                                'reject_by_finance'  => 'Menolak pengajuan (Finance)',
                                'pay'                => 'Menandai sebagai dibayar',
                                'cancel'             => 'Membatalkan pengajuan',
                            ];
                            @endphp
                            {{ $actionLabels[$log->action] ?? $log->action }}
                            @if($log->from_status && $log->to_status)
                                <span class="text-slate-400">
                                    — dari <x-status-badge :status="\App\Enums\ReimbursementStatus::from($log->from_status)"/>
                                    ke <x-status-badge :status="\App\Enums\ReimbursementStatus::from($log->to_status)"/>
                                </span>
                            @endif
                        </p>
                        @if($log->note)
                            <div class="mt-1.5 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-600 italic">
                                "{{ $log->note }}"
                            </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </x-card>

    {{-- ── Komentar ─────────────────────────────────────────────────────── --}}
    <x-card class="p-6">
        <h3 class="font-semibold text-slate-800 mb-5">Komentar ({{ $reimbursement->comments->count() }})</h3>

        @if($reimbursement->comments->isEmpty())
            <p class="text-sm text-slate-400 mb-5">Belum ada komentar.</p>
        @else
        <div class="space-y-4 mb-6">
            @foreach($reimbursement->comments as $comment)
            <div class="flex gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-sm font-bold flex-shrink-0">
                    {{ strtoupper(substr($comment->user->name, 0, 1)) }}
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-medium text-slate-800 text-sm">{{ $comment->user->name }}</span>
                        <span class="text-xs text-slate-400">{{ $comment->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="bg-slate-50 rounded-xl px-4 py-3 text-sm text-slate-700">{{ $comment->body }}</div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        @can('comment', $reimbursement)
        <form action="{{ route('reimbursements.comments.store', $reimbursement) }}" method="POST" class="flex gap-3">
            @csrf
            <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="flex-1 flex gap-2">
                <textarea name="body" rows="2" required maxlength="1000"
                          placeholder="Tulis komentar..."
                          class="flex-1 input-field text-sm resize-none"></textarea>
                <button type="submit" class="btn-primary self-end">Kirim</button>
            </div>
        </form>
        @endcan
    </x-card>

    {{-- Preview modal --}}
    <div x-show="showPreview" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80" style="display:none" @keydown.escape.window="showPreview = false">
        <div class="relative max-w-4xl w-full max-h-full">
            <button @click="showPreview = false" class="absolute -top-10 right-0 text-white hover:text-slate-300">
                <x-icon name="x-mark" class="w-6 h-6"/>
            </button>
            <template x-if="previewType === 'image'">
                <img :src="previewUrl" class="max-w-full max-h-[85vh] rounded-xl mx-auto object-contain">
            </template>
            <template x-if="previewType === 'pdf'">
                <iframe :src="previewUrl" class="w-full h-[85vh] rounded-xl"></iframe>
            </template>
        </div>
    </div>

</div>

@endsection
