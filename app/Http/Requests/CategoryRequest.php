<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $catId = $this->route('category')?->id;

        return [
            'name'             => ['required', 'string', 'max:100'],
            'code'             => ['required', 'string', 'max:10', Rule::unique('categories', 'code')->ignore($catId)],
            'requires_receipt' => ['boolean'],
            'is_active'        => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi.',
            'code.required' => 'Kode kategori wajib diisi.',
            'code.unique'   => 'Kode kategori sudah digunakan.',
        ];
    }
}
