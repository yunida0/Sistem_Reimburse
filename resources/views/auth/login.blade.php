@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 px-8 py-10">
    {{-- Logo & Header --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-blue-600 rounded-2xl mb-4">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Reimbust</h1>
        <p class="text-slate-500 text-sm mt-1">Sistem Reimbursement Internal</p>
    </div>

    {{-- Pesan sesi berakhir / error --}}
    @if(session('error'))
        <div class="mb-5 flex items-start gap-2.5 bg-amber-50 border border-amber-200 text-amber-800 text-sm px-4 py-3 rounded-lg">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Form --}}
    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Alamat Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-300 @enderror"
                   placeholder="nama@perusahaan.com">
            @error('email')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
            <input id="password" type="password" name="password" required
                   class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                   placeholder="••••••••">
            @error('password')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center">
            <input id="remember_me" type="checkbox" name="remember" class="w-4 h-4 text-blue-600 rounded border-slate-300">
            <label for="remember_me" class="ml-2 text-sm text-slate-600">Ingat saya</label>
        </div>

        <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Masuk
        </button>
    </form>
</div>

{{-- Demo accounts (local only) --}}
@if(app()->environment('local'))
<div class="mt-6 bg-slate-50 border border-slate-200 rounded-xl p-5">
    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Akun Demo</p>
    <div class="space-y-2">
        @foreach([
            ['admin@reimbust.test', 'Admin'],
            ['budi@reimbust.test', 'Atasan'],
            ['rudi@reimbust.test', 'Pegawai'],
            ['siti@reimbust.test', 'Pegawai'],
            ['sari@reimbust.test', 'Finance'],
            ['kasir@reimbust.test', 'Kasir'],
        ] as [$email, $role])
        <button type="button" onclick="fillLogin('{{ $email }}')"
                class="w-full text-left px-3 py-2 rounded-lg hover:bg-slate-100 transition-colors">
            <span class="text-sm font-medium text-slate-700">{{ $email }}</span>
            <span class="ml-2 text-xs text-slate-400">{{ $role }}</span>
        </button>
        @endforeach
    </div>
    <p class="text-xs text-slate-400 mt-3">Password semua akun: <code class="bg-slate-200 px-1 rounded">password</code></p>
</div>

<script>
function fillLogin(email) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = 'password';
}
</script>
@endif
@endsection
