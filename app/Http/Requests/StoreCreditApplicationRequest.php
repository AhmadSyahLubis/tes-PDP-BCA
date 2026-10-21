<?php

namespace App\Http\Requests;

use App\Models\CreditApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreCreditApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', CreditApplication::class);
    }

    public function rules(): array
    {
        return [
            'nik' => ['required', 'digits:16'],
            'name' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before:today'],
            'marital_status' => ['required', Rule::in(['belum_menikah', 'menikah', 'cerai'])],
            'spouse_name' => ['nullable', 'required_if:marital_status,menikah', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:25'],
            'address' => ['required', 'string', 'max:1000'],
            'dealer_name' => ['required', 'string', 'max:100'],
            'vehicle_brand' => ['required', 'string', 'max:100'],
            'vehicle_model' => ['required', 'string', 'max:100'],
            'vehicle_type' => ['required', 'string', 'max:100'],
            'vehicle_color' => ['required', 'string', 'max:50'],
            'vehicle_price' => ['required', 'numeric', 'min:1', 'max:9999999999999'],
            'down_payment' => ['required', 'numeric', 'min:0', 'lt:vehicle_price'],
            'tenor_months' => ['required', 'integer', 'min:1', 'max:120'],
            'monthly_installment' => ['required', 'numeric', 'min:1', 'max:9999999999999'],
            'ktp' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'kartu_keluarga' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'bukti_tanda_jadi' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
