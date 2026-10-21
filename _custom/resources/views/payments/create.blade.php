@extends('layouts.app')
@section('title', 'Form Pembayaran')
@section('page-title', 'Konfirmasi Pembayaran')

@section('content')
<div class="py-4 max-w-2xl">
    {{-- Ringkasan pengajuan --}}
    <x-card class="p-5 mb-6">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs text-slate-500 uppercase tracking-wider mb-1">Pengajuan</p>
                <p class="font-bold text-slate-800">{{ $reimbursement->title }}</p>
                <p class="text-sm text-slate-500 mt-0.5">{{ $reimbursement->code }} · {{ $reimbursement->user->name }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-slate-500 uppercase tracking-wider mb-1">Total</p>
                <p class="text-2xl font-bold text-blue-700"><x-money :amount="$reimbursement->total_amount"/></p>
            </div>
        </div>
    </x-card>

    <x-card class="p-6">
        <h2 class="font-semibold text-slate-800 mb-5">Detail Pembayaran</h2>

        <form action="{{ route('payments.store', $reimbursement) }}" method="POST"
              enctype="multipart/form-data"
              x-data="{ method: '{{ old('payment_method', '') }}', proofName: '' }"
              id="payment-form">
            @csrf

            <div class="space-y-5">
                {{-- Tanggal --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">
                        Tanggal Pembayaran <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="paid_at"
                           value="{{ old('paid_at', today()->toDateString()) }}"
                           max="{{ today()->toDateString() }}"
                           class="input-field max-w-xs @error('paid_at') input-error @enderror">
                    @error('paid_at') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                {{-- Metode --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">
                        Metode Pembayaran <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="payment_method" value="transfer"
                                   x-model="method"
                                   class="w-4 h-4 text-blue-600">
                            <span class="text-sm text-slate-700">Transfer Bank</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="payment_method" value="cash"
                                   x-model="method"
                                   class="w-4 h-4 text-blue-600">
                            <span class="text-sm text-slate-700">Tunai</span>
                        </label>
                    </div>
                    @error('payment_method') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                {{-- Nomor referensi (hanya transfer) --}}
                <div x-show="method === 'transfer'" x-transition>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">
                        Nomor Referensi / TRF <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="payment_reference"
                           value="{{ old('payment_reference') }}"
                           placeholder="Contoh: TRF-20261020-001"
                           class="input-field @error('payment_reference') input-error @enderror">
                    @error('payment_reference') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                {{-- Catatan --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Catatan Pembayaran</label>
                    <textarea name="payment_note" rows="2" maxlength="1000"
                              placeholder="Opsional..."
                              class="input-field">{{ old('payment_note') }}</textarea>
                </div>

                {{-- Upload bukti (WAJIB) --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">
                        Bukti Pembayaran / Transfer <span class="text-red-500">*</span>
                    </label>
                    <div class="border-2 border-dashed border-slate-300 rounded-xl p-6 text-center hover:border-blue-400 transition-colors"
                         :class="proofName ? 'border-emerald-400 bg-emerald-50' : ''">
                        <x-icon name="arrow-up-tray" class="w-8 h-8 text-slate-400 mx-auto mb-2"/>
                        <p class="text-sm text-slate-500 mb-1" x-show="!proofName">
                            Unggah bukti transfer atau bukti pembayaran
                        </p>
                        <p class="text-sm text-emerald-600 font-medium" x-show="proofName" x-text="proofName"></p>
                        <p class="text-xs text-slate-400 mt-1">JPG, PNG, WEBP, PDF · maks 5 MB</p>
                        <input type="file" name="proof" required accept=".jpg,.jpeg,.png,.webp,.pdf"
                               @change="proofName = $event.target.files[0]?.name || ''"
                               class="mt-3 block w-full text-sm text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    @error('proof') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Submit dengan modal konfirmasi --}}
            <div class="mt-6 flex gap-3">
                <a href="{{ route('payments.index') }}" class="btn-secondary">Batal</a>
                <button type="button"
                        @click="if(document.querySelector('[name=payment_method]:checked') && document.querySelector('[name=proof]').files.length > 0) $dispatch('open-confirm')"
                        class="btn-success">
                    Konfirmasi Pembayaran
                </button>
            </div>
        </form>
    </x-card>
</div>

{{-- Modal konfirmasi --}}
<div x-data="{ open: false }" @open-confirm.window="open = true">
    <div x-show="open" x-transition class="fixed inset-0 z-50 flex items-center justify-center px-4 bg-black/50" style="display:none">
        <div class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-xl">
            <h3 class="font-semibold text-slate-800 mb-2">Konfirmasi Pembayaran?</h3>
            <p class="text-sm text-slate-500 mb-4">
                Total: <strong><x-money :amount="$reimbursement->total_amount"/></strong><br>
                Aksi ini tidak dapat dibatalkan setelah berhasil.
            </p>
            <div class="flex gap-3 justify-end">
                <button @click="open = false" class="btn-secondary">Kembali</button>
                <button @click="document.getElementById('payment-form').submit()" class="btn-success">
                    Ya, Konfirmasi
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
