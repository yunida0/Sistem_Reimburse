@extends('layouts.app')

@section('title', 'Edit Pengajuan')
@section('page-title', 'Edit Pengajuan')

@section('content')

{{-- Alert revisi --}}
@if($reimbursement->status === \App\Enums\ReimbursementStatus::Revision)
<div class="mb-4 bg-amber-50 border border-amber-200 rounded-xl p-4 flex gap-3">
    <x-icon name="exclamation-triangle" class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5"/>
    <div>
        <p class="font-semibold text-amber-800">Pengajuan Perlu Revisi</p>
        <p class="text-sm text-amber-700 mt-1">
            Diminta oleh: <strong>{{ $reimbursement->revision_from?->label() }}</strong>
        </p>
        @php $lastReturn = $reimbursement->logs->where('to_status', 'REVISION')->last(); @endphp
        @if($lastReturn?->note)
            <p class="text-sm text-amber-700 mt-1">Catatan: {{ $lastReturn->note }}</p>
        @endif
    </div>
</div>
@endif

@include('reimbursements._form', [
    'categories'    => $categories,
    'reimbursement' => $reimbursement,
    'editMode'      => true,
])
@endsection
