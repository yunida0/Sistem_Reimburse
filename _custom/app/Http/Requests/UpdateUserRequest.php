<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name'          => ['required', 'string', 'max:100'],
            'email'         => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'password'      => ['nullable', 'string', 'min:8'],
            'role'          => ['required', Rule::enum(UserRole::class)],
            'department_id' => ['nullable', 'exists:departments,id'],
            'manager_id'    => ['nullable', 'exists:users,id', Rule::notIn([$userId])],
            'is_active'     => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'       => 'Email sudah digunakan pengguna lain.',
            'password.min'       => 'Password minimal 8 karakter.',
            'manager_id.not_in'  => 'Pengguna tidak bisa menjadi atasannya sendiri.',
        ];
    }
}
