<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $deptId = $this->route('department')?->id;

        return [
            'name'      => ['required', 'string', 'max:100'],
            'code'      => ['required', 'string', 'max:10', Rule::unique('departments', 'code')->ignore($deptId)],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama departemen wajib diisi.',
            'code.required' => 'Kode departemen wajib diisi.',
            'code.unique'   => 'Kode departemen sudah digunakan.',
        ];
    }
}
