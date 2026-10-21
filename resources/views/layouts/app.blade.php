<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Reimbust') — Reimbust</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased" style="font-family: 'Inter', sans-serif;">

<div x-data="{ sidebarOpen: false }" class="flex h-screen overflow-hidden">

    {{-- ── Sidebar overlay (mobile) ──────────────────────────────────── --}}
    <div x-show="sidebarOpen"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="fixed inset-0 z-20 bg-black/50 lg:hidden"></div>

    {{-- ── Sidebar ────────────────────────────────────────────────────── --}}
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-30 w-64 flex flex-col bg-slate-900 transition-transform duration-300 lg:static lg:translate-x-0">

        {{-- Logo --}}
        <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-700">
            <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="text-white font-bold text-lg tracking-wide">Reimbust</span>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            @php $user = auth()->user(); @endphp

            <x-nav-item route="dashboard" icon="home">Dashboard</x-nav-item>
            <x-nav-item route="reimbursements.index" icon="document-text">Pengajuan</x-nav-item>

            @if($user->hasSubordinates())
                <x-nav-item route="approvals.index" icon="check-circle">Persetujuan</x-nav-item>
            @endif

            @if($user->hasRole(\App\Enums\UserRole::Finance))
                <x-nav-item route="finance.index" icon="shield-check">Verifikasi Finance</x-nav-item>
            @endif

            @if($user->hasRole(\App\Enums\UserRole::Cashier))
                <x-nav-item route="payments.index" icon="banknotes">Pembayaran</x-nav-item>
            @endif

            @if($user->hasAnyRole([\App\Enums\UserRole::Finance->value, \App\Enums\UserRole::Admin->value]))
                <x-nav-item route="reports.index" icon="chart-bar">Laporan</x-nav-item>
            @endif

            @if($user->hasRole(\App\Enums\UserRole::Admin))
                <div class="pt-3 pb-1 px-3">
                    <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Master Data</p>
                </div>
                <x-nav-item route="admin.users.index" icon="users">Pengguna</x-nav-item>
                <x-nav-item route="admin.departments.index" icon="building-office">Departemen</x-nav-item>
                <x-nav-item route="admin.categories.index" icon="tag">Kategori</x-nav-item>
            @endif

            <div class="pt-3 pb-1 px-3">
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Akun</p>
            </div>

            {{-- Notifikasi --}}
            <a href="{{ route('notifications.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors {{ request()->routeIs('notifications.*') ? 'bg-blue-600 text-white' : '' }}">
                <x-icon name="bell" class="w-5 h-5"/>
                <span class="flex-1 text-sm font-medium">Notifikasi</span>
                @php $unread = auth()->user()->unreadNotifications->count(); @endphp
                @if($unread > 0)
                    <span class="bg-red-500 text-white text-xs rounded-full px-2 py-0.5 min-w-[1.25rem] text-center">
                        {{ $unread > 99 ? '99+' : $unread }}
                    </span>
                @endif
            </a>

            <x-nav-item route="profile.edit" icon="user-circle">Profil</x-nav-item>

            {{-- Logout --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-red-400 hover:bg-red-500/10 hover:text-red-300 transition-colors">
                    <x-icon name="logout" class="w-5 h-5 flex-shrink-0"/>
                    <span>Keluar</span>
                </button>
            </form>
        </nav>

        {{-- User info bottom --}}
        <div class="px-4 py-4 border-t border-slate-700">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-bold">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-slate-400">{{ auth()->user()->role->label() }}</p>
                </div>
            </div>
        </div>
    </aside>

    {{-- ── Main content ──────────────────────────────────────────────── --}}
    <div class="flex-1 flex flex-col overflow-hidden">

        {{-- Topbar --}}
        <header class="bg-white border-b border-slate-200 px-4 py-3 flex items-center gap-4">
            {{-- Mobile menu button --}}
            <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <h1 class="flex-1 text-lg font-semibold text-slate-800">@yield('page-title')</h1>

            {{-- Notif bell --}}
            <a href="{{ route('notifications.index') }}" class="relative p-2 text-slate-500 hover:text-slate-700">
                <x-icon name="bell" class="w-5 h-5"/>
                @if(auth()->user()->unreadNotifications->count() > 0)
                    <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                @endif
            </a>

            {{-- User menu --}}
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 p-2 rounded-lg hover:bg-slate-100">
                    <div class="w-7 h-7 rounded-full bg-blue-500 flex items-center justify-center text-white text-xs font-bold">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <span class="hidden sm:block font-medium">{{ auth()->user()->name }}</span>
                </button>
                <div x-show="open" @click.outside="open = false" x-transition
                     class="absolute right-0 mt-1 w-48 bg-white rounded-xl shadow-lg border border-slate-200 py-1 z-50">
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Profil Saya</a>
                    <div class="border-t border-slate-100 my-1"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Keluar</button>
                    </form>
                </div>
            </div>
        </header>

        {{-- Flash messages --}}
        <div class="px-6 pt-4">
            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                     class="mb-4 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl">
                    <x-icon name="check-circle" class="w-5 h-5 flex-shrink-0"/>
                    <span class="text-sm">{{ session('success') }}</span>
                    <button @click="show = false" class="ml-auto text-emerald-500 hover:text-emerald-700">✕</button>
                </div>
            @endif

            @if(session('error'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                     class="mb-4 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                    <x-icon name="exclamation-circle" class="w-5 h-5 flex-shrink-0"/>
                    <span class="text-sm">{{ session('error') }}</span>
                    <button @click="show = false" class="ml-auto text-red-400 hover:text-red-700">✕</button>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                    <div class="flex items-center gap-2 font-medium text-sm mb-2">
                        <x-icon name="exclamation-circle" class="w-4 h-4"/>
                        Terdapat kesalahan:
                    </div>
                    <ul class="list-disc pl-5 space-y-1 text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{-- Page content --}}
        <main class="flex-1 overflow-y-auto px-6 pb-6">
            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
