<?php

namespace App\Http\Requests;

class PromoRedeemRequest extends PromoValidationRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'invoice_number' => ['required', 'string', 'max:120'],
            'invoice_amount' => ['required', 'numeric', 'min:0.01'],
        ]);
    }
}
