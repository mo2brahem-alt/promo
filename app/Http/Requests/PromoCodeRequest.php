<?php

namespace App\Http\Requests;

use App\Models\PromoCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromoCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManagePromos() ?? false;
    }

    public function rules(): array
    {
        $promoCode = $this->route('promoCode');
        $promoCodeId = $promoCode?->id;
        $hasRedemptions = $promoCode && $promoCode->redemptions()->exists();
        $sensitive = $hasRedemptions ? ['prohibited'] : ['nullable'];

        return [
            'code' => array_merge($hasRedemptions ? ['prohibited'] : ['required'], ['string', 'max:80', Rule::unique('promo_codes', 'code')->ignore($promoCodeId)]),
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'discount_type' => array_merge($sensitive, [Rule::in([PromoCode::DISCOUNT_PERCENTAGE])]),
            'discount_value' => array_merge($hasRedemptions ? ['prohibited'] : ['required'], ['numeric', 'min:0', 'max:100']),
            'max_invoice_amount' => array_merge($sensitive, ['numeric', 'min:0.01']),
            'max_discount_amount' => array_merge($sensitive, ['numeric', 'min:0.01']),
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'max_total_uses' => array_merge($sensitive, ['integer', 'min:1']),
            'max_uses_per_customer' => array_merge($hasRedemptions ? ['prohibited'] : ['required'], ['integer', 'min:1']),
            'is_active' => ['boolean'],
            'customers' => ['required', 'array', 'min:1'],
            'customers.*' => ['integer', 'exists:customers,id'],
            'branches' => ['required', 'array', 'min:1'],
            'branches.*' => ['integer', 'exists:branches,id'],
        ];
    }
}
