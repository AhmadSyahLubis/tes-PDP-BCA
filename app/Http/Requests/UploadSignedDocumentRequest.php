<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UploadSignedDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('uploadSigned', $this->route('application'));
    }

    public function rules(): array
    {
        return ['signed_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240']];
    }
}
