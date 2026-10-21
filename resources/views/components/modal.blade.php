@props(['name', 'title' => null, 'show' => false])

<div x-data="{ open: @js((bool) $show) }"
     x-on:open-modal.window="if ($event.detail === @js($name)) open = true"
     x-on:close-modal.window="if ($event.detail === @js($name)) open = false"
     x-on:open-modal-{{ $name }}.window="open = true"
     x-on:keydown.escape.window="open = false">
    {{-- Trigger slot --}}
    <div @click="open = true">{{ $trigger ?? '' }}</div>

    {{-- Modal backdrop --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center px-4"
         style="display: none;">
        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
        <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6" @click.stop>
            <div class="flex items-center justify-between mb-4">
                @if ($title)
                    <h3 class="text-lg font-semibold text-slate-800">{{ $title }}</h3>
                @else
                    <span></span>
                @endif
                <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-600">
                    <x-icon name="x-mark" class="w-5 h-5"/>
                </button>
            </div>
            {{ $slot }}
        </div>
    </div>
</div>
