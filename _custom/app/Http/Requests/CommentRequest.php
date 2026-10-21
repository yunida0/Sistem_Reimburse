<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:' . config('reimbust.max_comment_length', 1000)],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'Komentar tidak boleh kosong.',
            'body.max'      => 'Komentar maksimal ' . config('reimbust.max_comment_length', 1000) . ' karakter.',
        ];
    }
}
