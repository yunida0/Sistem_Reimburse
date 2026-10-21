@props(['class' => ''])
<div {{ $attributes->merge(['class' => "bg-white rounded-xl border border-slate-200 shadow-sm $class"]) }}>
    {{ $slot }}
</div>
