@php $editMode = isset($user) && $user !== null; @endphp

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama <span class="text-red-500">*</span></label>
    <input type="text" name="name" value="{{ old('name', $user?->name) }}" required class="input-field @error('name') input-error @enderror">
    @error('name') <p class="field-error">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email <span class="text-red-500">*</span></label>
    <input type="email" name="email" value="{{ old('email', $user?->email) }}" required class="input-field @error('email') input-error @enderror">
    @error('email') <p class="field-error">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">
        Password {{ $editMode ? '(kosongkan jika tidak diubah)' : '*' }}
    </label>
    <input type="password" name="password" {{ !$editMode ? 'required' : '' }}
           class="input-field @error('password') input-error @enderror">
    @error('password') <p class="field-error">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Peran <span class="text-red-500">*</span></label>
    <select name="role" class="input-field">
        @foreach($roles as $role)
            <option value="{{ $role->value }}" {{ old('role', $user?->role?->value) === $role->value ? 'selected' : '' }}>
                {{ $role->label() }}
            </option>
        @endforeach
    </select>
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Departemen</label>
    <select name="department_id" class="input-field">
        <option value="">— Tidak ada —</option>
        @foreach($departments as $dept)
            <option value="{{ $dept->id }}" {{ old('department_id', $user?->department_id) == $dept->id ? 'selected' : '' }}>
                {{ $dept->name }}
            </option>
        @endforeach
    </select>
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Atasan Langsung</label>
    <select name="manager_id" class="input-field">
        <option value="">— Tidak ada —</option>
        @foreach($managers as $m)
            <option value="{{ $m->id }}" {{ old('manager_id', $user?->manager_id) == $m->id ? 'selected' : '' }}>
                {{ $m->name }} ({{ $m->role->label() }})
            </option>
        @endforeach
    </select>
</div>

<div class="flex items-center gap-2">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" id="is_active"
           {{ old('is_active', $user?->is_active ?? true) ? 'checked' : '' }}
           class="w-4 h-4 text-blue-600 rounded border-slate-300">
    <label for="is_active" class="text-sm text-slate-700">Akun Aktif</label>
</div>
