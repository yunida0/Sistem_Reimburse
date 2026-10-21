@props(['route', 'icon' => 'circle'])

@php
    $active = request()->routeIs($route) || request()->routeIs(str_replace('.index', '.*', $route));
@endphp

<a href="{{ route($route) }}"
   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
          {{ $active ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
    <x-icon :name="$icon" class="w-5 h-5 flex-shrink-0"/>
    <span>{{ $slot }}</span>
</a>
