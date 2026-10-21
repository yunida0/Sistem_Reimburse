@props(['status'])

@php
use App\Enums\ReimbursementStatus;
$s = $status instanceof ReimbursementStatus ? $status : ReimbursementStatus::from($status);
$colors = [
    'slate'   => 'bg-slate-100 text-slate-700',
    'blue'    => 'bg-blue-100 text-blue-700',
    'amber'   => 'bg-amber-100 text-amber-700',
    'indigo'  => 'bg-indigo-100 text-indigo-700',
    'cyan'    => 'bg-cyan-100 text-cyan-700',
    'emerald' => 'bg-emerald-100 text-emerald-700',
    'red'     => 'bg-red-100 text-red-700',
    'gray'    => 'bg-gray-100 text-gray-600',
];
$cls = $colors[$s->color()] ?? 'bg-gray-100 text-gray-600';
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $cls }}">
    {{ $s->label() }}
</span>
