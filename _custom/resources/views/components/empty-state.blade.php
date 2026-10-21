@props(['icon' => 'document-text', 'title', 'description' => null])

<div class="text-center py-12">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 mb-4">
        <x-icon :name="$icon" class="w-8 h-8 text-slate-400"/>
    </div>
    <h3 class="text-base font-semibold text-slate-700 mb-1">{{ $title }}</h3>
    @if($description)
        <p class="text-sm text-slate-500 mb-4">{{ $description }}</p>
    @endif
    @if($slot->isNotEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
