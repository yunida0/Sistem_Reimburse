@extends('layouts.app')
@section('title', 'Notifikasi')
@section('page-title', 'Notifikasi')

@section('content')
<div class="py-4 space-y-4">
    <div class="flex items-center justify-between">
        <p class="text-sm text-slate-500">{{ auth()->user()->unreadNotifications->count() }} belum dibaca</p>
        <form action="{{ route('notifications.read-all') }}" method="POST">
            @csrf
            <button type="submit" class="btn-secondary text-sm">Tandai semua dibaca</button>
        </form>
    </div>

    <x-card>
        @if($notifications->isEmpty())
            <x-empty-state icon="bell" title="Tidak ada notifikasi" description="Anda belum memiliki notifikasi."/>
        @else
        <div class="divide-y divide-slate-50">
            @foreach($notifications as $notif)
            @php $data = $notif->data; @endphp
            <form action="{{ route('notifications.read', $notif->id) }}" method="POST">
                @csrf
                <button type="submit"
                        class="w-full text-left flex items-start gap-4 px-5 py-4 hover:bg-slate-50 transition-colors
                               {{ is_null($notif->read_at) ? 'bg-blue-50/50' : '' }}">
                    <div class="w-2 h-2 rounded-full mt-2 flex-shrink-0
                                {{ is_null($notif->read_at) ? 'bg-blue-500' : 'bg-transparent' }}"></div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-slate-800 text-sm">{{ $data['title'] ?? 'Notifikasi' }}</p>
                        <p class="text-sm text-slate-500 mt-0.5">{{ $data['message'] ?? '' }}</p>
                        <p class="text-xs text-slate-400 mt-1">{{ $notif->created_at->diffForHumans() }}</p>
                    </div>
                    @if($data['reimbursement_code'] ?? null)
                    <span class="text-xs font-mono text-slate-400">{{ $data['reimbursement_code'] }}</span>
                    @endif
                </button>
            </form>
            @endforeach
        </div>
        <div class="px-5 py-4 border-t border-slate-100">
            {{ $notifications->links() }}
        </div>
        @endif
    </x-card>
</div>
@endsection
