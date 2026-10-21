<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApprovalActionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'min:1', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => 'Catatan wajib diisi untuk aksi ini.',
            'note.max'      => 'Catatan maksimal 1000 karakter.',
        ];
    }
}
