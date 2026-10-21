<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:100'],
            'email'         => ['required', 'email', 'unique:users,email'],
            'password'      => ['required', 'string', 'min:8'],
            'role'          => ['required', Rule::enum(UserRole::class)],
            'department_id' => ['nullable', 'exists:departments,id'],
            'manager_id'    => ['nullable', 'exists:users,id'],
            'is_active'     => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'Nama wajib diisi.',
            'email.required'    => 'Email wajib diisi.',
            'email.unique'      => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi saat membuat pengguna baru.',
            'password.min'      => 'Password minimal 8 karakter.',
            'role.required'     => 'Peran wajib dipilih.',
        ];
    }
}
