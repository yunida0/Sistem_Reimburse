@props(['days'])

@php
$warn   = config('reimbust.aging_warning_days', 3);
$danger = config('reimbust.aging_danger_days', 5);
if ($days >= $danger) {
    $cls = 'bg-red-100 text-red-700';
} elseif ($days >= $warn) {
    $cls = 'bg-amber-100 text-amber-700';
} else {
    $cls = null;
}
@endphp

@if($cls)
<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $cls }}">
    <x-icon name="clock" class="w-3 h-3"/>
    {{ $days }} hari
</span>
@else
<span class="text-xs text-slate-400">{{ $days }} hari</span>
@endif
