<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama <span class="text-red-500">*</span></label>
    <input type="text" name="name" value="{{ old('name', $category?->name) }}" required class="input-field @error('name') input-error @enderror">
    @error('name') <p class="field-error">{{ $message }}</p> @enderror
</div>
<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Kode <span class="text-red-500">*</span></label>
    <input type="text" name="code" value="{{ old('code', $category?->code) }}" required maxlength="10" class="input-field font-mono @error('code') input-error @enderror">
    @error('code') <p class="field-error">{{ $message }}</p> @enderror
</div>
<div class="flex items-center gap-2">
    <input type="hidden" name="requires_receipt" value="0">
    <input type="checkbox" name="requires_receipt" value="1" id="requires_receipt"
           {{ old('requires_receipt', $category?->requires_receipt ?? true) ? 'checked' : '' }}
           class="w-4 h-4 text-blue-600 rounded">
    <label for="requires_receipt" class="text-sm text-slate-700">Wajib Bukti Transaksi</label>
</div>
<div class="flex items-center gap-2">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" id="is_active_cat"
           {{ old('is_active', $category?->is_active ?? true) ? 'checked' : '' }}
           class="w-4 h-4 text-blue-600 rounded">
    <label for="is_active_cat" class="text-sm text-slate-700">Aktif</label>
</div>
