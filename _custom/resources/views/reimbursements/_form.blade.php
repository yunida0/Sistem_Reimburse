@php
$editMode      = $editMode ?? false;
$reimbursement = $reimbursement ?? null;

$formAction = $editMode
    ? route('reimbursements.update', $reimbursement)
    : route('reimbursements.store');

// Existing items (edit mode)
$existingItems = $editMode ? $reimbursement->items : collect();

// Category data untuk JS
$categoryData = $categories->map(fn($c) => [
    'id'               => $c->id,
    'name'             => $c->name,
    'requires_receipt' => $c->requires_receipt,
])->values()->toArray();
@endphp

<div class="py-4">
    <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data"
          x-data="reimbursementForm()"
          id="reimbursement-form">
        @csrf
        @if($editMode) @method('PUT') @endif
        <input type="hidden" name="action" x-model="actionMode">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- ── Kolom kiri / tengah ────────────────────────────────── --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Section: Info Pengajuan --}}
                <x-card class="p-6">
                    <h2 class="font-semibold text-slate-800 mb-4">Informasi Pengajuan</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">
                                Judul <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="title"
                                   value="{{ old('title', $reimbursement?->title) }}"
                                   maxlength="150" placeholder="Contoh: Perjalanan Dinas Yogyakarta"
                                   class="input-field @error('title') input-error @enderror">
                            @error('title') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Deskripsi</label>
                            <textarea name="description" rows="3" maxlength="2000"
                                      placeholder="Tujuan, aktivitas, atau keterangan lain (opsional)..."
                                      class="input-field @error('description') input-error @enderror">{{ old('description', $reimbursement?->description) }}</textarea>
                            @error('description') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </x-card>

                {{-- Section: Rincian Biaya --}}
                <x-card class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-semibold text-slate-800">Rincian Biaya</h2>
                        <p class="text-xs text-slate-400">Pastikan tanggal dan nominal pada struk terlihat jelas.</p>
                    </div>

                    <div class="space-y-6" id="items-container">
                        <template x-for="(item, index) in items" :key="item.key">
                            <div class="border border-slate-200 rounded-xl p-5 relative">
                                {{-- Item header --}}
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-sm font-semibold text-slate-600" x-text="`Item #${index + 1}`"></span>
                                    <button type="button" @click="removeItem(index)"
                                            class="text-red-400 hover:text-red-600 p-1 rounded"
                                            x-show="items.length > 1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>

                                <input type="hidden" :name="`items[${index}][id]`" :value="item.id">

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    {{-- Tanggal --}}
                                    <div>
                                        <label class="block text-xs font-medium text-slate-500 mb-1">
                                            Tanggal Pengeluaran <span class="text-red-500">*</span>
                                        </label>
                                        <input type="date" :name="`items[${index}][expense_date]`"
                                               x-model="item.expense_date"
                                               :max="today"
                                               class="input-field text-sm">
                                    </div>

                                    {{-- Kategori --}}
                                    <div>
                                        <label class="block text-xs font-medium text-slate-500 mb-1">
                                            Kategori <span class="text-red-500">*</span>
                                        </label>
                                        <select :name="`items[${index}][category_id]`"
                                                x-model="item.category_id"
                                                @change="item.requires_receipt = getCategoryRequiresReceipt(item.category_id)"
                                                class="input-field text-sm">
                                            <option value="">Pilih kategori...</option>
                                            @foreach($categories as $cat)
                                                <option value="{{ $cat->id }}" data-requires="{{ $cat->requires_receipt ? '1' : '0' }}">
                                                    {{ $cat->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Deskripsi --}}
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-medium text-slate-500 mb-1">
                                            Deskripsi <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" :name="`items[${index}][description]`"
                                               x-model="item.description"
                                               placeholder="Keterangan pengeluaran..."
                                               class="input-field text-sm">
                                    </div>

                                    {{-- Vendor --}}
                                    <div>
                                        <label class="block text-xs font-medium text-slate-500 mb-1">Vendor / Toko</label>
                                        <input type="text" :name="`items[${index}][vendor]`"
                                               x-model="item.vendor"
                                               placeholder="Nama vendor (opsional)"
                                               class="input-field text-sm">
                                    </div>

                                    {{-- Nominal --}}
                                    <div>
                                        <label class="block text-xs font-medium text-slate-500 mb-1">
                                            Nominal <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-500 font-medium">Rp</span>
                                            <input type="number" :name="`items[${index}][amount]`"
                                                   x-model.number="item.amount"
                                                   @input="recalculate()"
                                                   min="1" max="999999999" step="1"
                                                   placeholder="0"
                                                   class="input-field text-sm pl-9">
                                        </div>
                                    </div>
                                </div>

                                {{-- Upload bukti --}}
                                <div class="mt-4">
                                    <label class="block text-xs font-medium text-slate-500 mb-1">
                                        Bukti Transaksi
                                        <span x-show="item.requires_receipt" class="text-red-500">*</span>
                                        <span class="text-slate-400">(JPG, PNG, WEBP, PDF · maks 5 MB/file)</span>
                                    </label>

                                    {{-- Lampiran lama (edit mode) --}}
                                    <template x-if="item.existingAttachments && item.existingAttachments.length > 0">
                                        <div class="flex flex-wrap gap-2 mb-2">
                                            <template x-for="att in item.existingAttachments" :key="att.id">
                                                <div class="flex items-center gap-2 bg-slate-100 rounded-lg px-3 py-1.5 text-xs">
                                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                                    </svg>
                                                    <span x-text="att.original_name" class="text-slate-700 max-w-[120px] truncate"></span>
                                                    <a :href="`/attachments/${att.id}`" target="_blank" class="text-blue-500 hover:text-blue-700">Lihat</a>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <input type="file" :name="`items[${index}][receipts][]`"
                                           multiple accept=".jpg,.jpeg,.png,.webp,.pdf"
                                           class="block w-full text-sm text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Tombol tambah item --}}
                    <button type="button" @click="addItem()"
                            class="mt-4 flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 font-medium py-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Item
                    </button>
                </x-card>

            </div>

            {{-- ── Kolom kanan: Ringkasan sticky ─────────────────────── --}}
            <div class="lg:col-span-1">
                <div class="sticky top-4 space-y-4">
                    <x-card class="p-5">
                        <h3 class="font-semibold text-slate-800 mb-4">Ringkasan</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between text-slate-600">
                                <span>Jumlah item</span>
                                <span x-text="itemCount + ' item'" class="font-medium"></span>
                            </div>
                            <div class="border-t border-slate-100 my-2"></div>
                            <div class="flex justify-between text-slate-800">
                                <span class="font-semibold">Total</span>
                                <span class="font-bold text-blue-700" x-text="'Rp' + total.toLocaleString('id-ID')"></span>
                            </div>
                        </div>

                        <div class="mt-5 space-y-2">
                            <button type="button" @click="doSubmit('draft')"
                                    class="w-full btn-secondary">
                                Simpan Draf
                            </button>
                            <button type="button" @click="doSubmit('submit')"
                                    class="w-full btn-primary">
                                Simpan & Ajukan
                            </button>
                        </div>
                    </x-card>

                    <x-card class="p-4 bg-blue-50 border-blue-100">
                        <p class="text-xs text-blue-700">
                            <strong>💡 Tips:</strong> Pastikan tanggal dan nominal pada setiap struk terlihat jelas sebelum mengunggah.
                        </p>
                    </x-card>
                </div>
            </div>

        </div>

    </form>
</div>

@push('scripts')
@php
    $existingItemsJson = $existingItems->map(fn ($i) => [
        'id'          => $i->id,
        'expense_date'=> $i->expense_date?->format('Y-m-d'),
        'category_id' => (string) $i->category_id,
        'description' => $i->description,
        'vendor'      => $i->vendor ?? '',
        'amount'      => $i->amount,
        'requires_receipt' => $i->category->requires_receipt ?? false,
        'existingAttachments' => $i->attachments->map(fn ($a) => [
            'id' => $a->id,
            'original_name' => $a->original_name,
        ])->values(),
    ])->values();
@endphp
<script>
const CATEGORIES = @json($categoryData);

function reimbursementForm() {
    return {
        today: new Date().toISOString().split('T')[0],
        actionMode: 'draft',
        items: [],
        keyCounter: 0,

        init() {
            const existing = @json($existingItemsJson);

            if (existing.length > 0) {
                this.items = existing.map(i => ({ ...i, key: this.keyCounter++ }));
            } else {
                this.addItem();
            }
        },

        addItem() {
            this.items.push({
                key: this.keyCounter++,
                id: null,
                expense_date: '',
                category_id: '',
                description: '',
                vendor: '',
                amount: 0,
                requires_receipt: true,
                existingAttachments: [],
            });
        },

        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },

        getCategoryRequiresReceipt(categoryId) {
            const cat = CATEGORIES.find(c => c.id == categoryId);
            return cat ? cat.requires_receipt : true;
        },

        recalculate() {
            // Reactive — total computed below
        },

        get itemCount() {
            return this.items.filter(i => i.description || i.amount > 0).length;
        },

        get total() {
            return this.items.reduce((sum, i) => sum + (parseInt(i.amount) || 0), 0);
        },

        doSubmit(mode) {
            this.actionMode = mode;
            this.$nextTick(() => {
                // $el di dalam event handler menunjuk ke tombol, bukan form.
                document.getElementById('reimbursement-form').submit();
            });
        },

        submitForm(e) {
            // tidak digunakan
        }
    };
}
</script>
@endpush
