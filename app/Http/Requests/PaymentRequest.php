<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'paid_at'           => ['required', 'date', 'before_or_equal:today'],
            'payment_method'    => ['required', Rule::enum(PaymentMethod::class)],
            'payment_reference' => [
                Rule::requiredIf($this->payment_method === PaymentMethod::Transfer->value),
                'nullable', 'string', 'max:100',
            ],
            'payment_note'      => ['nullable', 'string', 'max:1000'],
            'proof'             => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:' . config('reimbust.max_file_kb', 5120)],
        ];
    }

    public function messages(): array
    {
        return [
            'paid_at.required'              => 'Tanggal pembayaran wajib diisi.',
            'paid_at.before_or_equal'       => 'Tanggal pembayaran tidak boleh di masa depan.',
            'payment_method.required'       => 'Metode pembayaran wajib dipilih.',
            'payment_reference.required_if' => 'Nomor referensi wajib diisi untuk pembayaran transfer.',
            'proof.required'                => 'Bukti pembayaran wajib diunggah.',
            'proof.mimes'                   => 'Format bukti harus JPG, PNG, WEBP, atau PDF.',
            'proof.max'                     => 'Ukuran bukti maksimal 5 MB.',
        ];
    }
}
