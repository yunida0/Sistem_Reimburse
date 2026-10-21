<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReimbursementRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'                      => ['required', 'string', 'max:150'],
            'description'                => ['nullable', 'string', 'max:2000'],
            'items'                      => ['nullable', 'array'],
            'items.*.id'                 => ['nullable', 'integer'],
            'items.*.category_id'        => ['required_with:items.*.description', 'integer', 'exists:categories,id'],
            'items.*.expense_date'       => ['required_with:items.*.description', 'date', 'before_or_equal:today'],
            'items.*.description'        => ['required_with:items.*.category_id', 'string', 'max:255'],
            'items.*.vendor'             => ['nullable', 'string', 'max:150'],
            'items.*.amount'             => ['required_with:items.*.description', 'integer', 'min:1', 'max:999999999'],
            'items.*.receipts'           => ['nullable', 'array', 'max:' . config('reimbust.max_files_per_item', 5)],
            'items.*.receipts.*'         => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:' . config('reimbust.max_file_kb', 5120)],
            'action'                     => ['nullable', 'in:draft,submit'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'                  => 'Judul pengajuan wajib diisi.',
            'title.max'                        => 'Judul maksimal 150 karakter.',
            'description.max'                  => 'Deskripsi maksimal 2000 karakter.',
            'items.*.category_id.required_with'=> 'Kategori wajib dipilih.',
            'items.*.category_id.exists'       => 'Kategori tidak valid.',
            'items.*.expense_date.required_with'=> 'Tanggal pengeluaran wajib diisi.',
            'items.*.expense_date.before_or_equal'=> 'Tanggal pengeluaran tidak boleh melebihi hari ini.',
            'items.*.description.required_with'=> 'Deskripsi item wajib diisi.',
            'items.*.amount.required_with'     => 'Nominal wajib diisi.',
            'items.*.amount.min'               => 'Nominal minimal Rp1.',
            'items.*.amount.max'               => 'Nominal maksimal Rp999.999.999.',
            'items.*.receipts.max'             => 'Maksimal ' . config('reimbust.max_files_per_item', 5) . ' file per item.',
            'items.*.receipts.*.mimes'         => 'Format file harus JPG, PNG, WEBP, atau PDF.',
            'items.*.receipts.*.max'           => 'Ukuran file maksimal 5 MB.',
        ];
    }
}
