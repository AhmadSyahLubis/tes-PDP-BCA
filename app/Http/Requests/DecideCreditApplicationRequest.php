<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class DecideCreditApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('decide', $this->route('application'));
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'decision_note' => ['nullable', 'required_if:decision,rejected', 'string', 'max:1000'],
        ];
    }
}
