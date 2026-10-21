<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class DisburseCreditApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('disburse', $this->route('application'));
    }

    public function rules(): array
    {
        return [
            'reference_no' => ['required', 'string', 'max:100', Rule::unique('disbursements', 'reference_no')],
            'disbursed_on' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
