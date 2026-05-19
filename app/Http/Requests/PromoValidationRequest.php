<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PromoValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('seller') ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:80'],
            'customer_phone' => ['required', 'string', 'max:30'],
        ];
    }
}
