<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManagePromos() ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
            'duplicate_strategy' => ['required', Rule::in(['update', 'skip'])],
        ];
    }
}
