<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\PromoCode;
use App\Models\PromoCodeRedemption;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PromoCodeRedemptionService
{
    public function validatePromo(string $code, string $customerPhone, User $seller): array
    {
        $phone = PhoneNormalizer::normalize($customerPhone);
        $promoCode = PromoCode::with(['customers', 'branches'])
            ->where('code', strtoupper(trim($code)))
            ->first();

        if (! $promoCode) {
            throw ValidationException::withMessages(['code' => 'الكود غير موجود.']);
        }

        if (! $promoCode->is_active) {
            throw ValidationException::withMessages(['code' => 'الكود غير نشط.']);
        }

        $customer = Customer::where('phone', $phone)->first();

        if (! $customer) {
            throw ValidationException::withMessages(['customer_phone' => 'رقم الجوال غير مسجل.']);
        }

        if (! $customer->is_active) {
            throw ValidationException::withMessages(['customer_phone' => 'العميل غير نشط.']);
        }

        if (! $promoCode->customers->contains('id', $customer->id)) {
            throw ValidationException::withMessages(['customer_phone' => 'العميل غير مسموح له باستخدام هذا الكود.']);
        }

        $branch = $this->sellerBranchForPromo($seller, $promoCode);
        $this->assertTimeWindow($promoCode);
        $this->assertUsageLimits($promoCode, $customer);

        return [
            'promo_code' => $this->promoPayload($promoCode, $customer, $branch),
            'customer' => $this->customerPayload($customer),
            'branch' => $this->branchPayload($branch),
            'remaining_uses' => $this->remainingUses($promoCode, $customer),
        ];
    }

    public function redeem(array $payload, User $seller): PromoCodeRedemption
    {
        return DB::transaction(function () use ($payload, $seller) {
            $code = strtoupper(trim((string) $payload['code']));
            $phone = PhoneNormalizer::normalize((string) $payload['customer_phone']);

            $promoCode = PromoCode::where('code', $code)->lockForUpdate()->first();
            if (! $promoCode) {
                throw ValidationException::withMessages(['code' => 'الكود غير موجود.']);
            }

            $customer = Customer::where('phone', $phone)->lockForUpdate()->first();
            if (! $customer) {
                throw ValidationException::withMessages(['customer_phone' => 'رقم الجوال غير مسجل.']);
            }

            $promoCode->load(['customers', 'branches']);
            $branch = $this->sellerBranchForPromo($seller, $promoCode, true);

            if (! $promoCode->is_active) {
                throw ValidationException::withMessages(['code' => 'الكود غير نشط.']);
            }

            if (! $customer->is_active) {
                throw ValidationException::withMessages(['customer_phone' => 'العميل غير نشط.']);
            }

            if (! $promoCode->customers->contains('id', $customer->id)) {
                throw ValidationException::withMessages(['customer_phone' => 'العميل غير مسموح له باستخدام هذا الكود.']);
            }

            $this->assertTimeWindow($promoCode);
            $this->assertUsageLimits($promoCode, $customer);

            $invoiceAmount = round((float) $payload['invoice_amount'], 2);
            if ($promoCode->max_invoice_amount !== null && $invoiceAmount > (float) $promoCode->max_invoice_amount) {
                throw ValidationException::withMessages([
                    'invoice_amount' => 'قيمة الفاتورة تتجاوز الحد الأقصى المسموح لهذا البرومو.',
                ]);
            }

            $discountAmount = round($invoiceAmount * (float) $promoCode->discount_value / 100, 2);
            if ($promoCode->max_discount_amount !== null) {
                $discountAmount = min($discountAmount, (float) $promoCode->max_discount_amount);
            }

            return PromoCodeRedemption::create([
                'promo_code_id' => $promoCode->id,
                'customer_id' => $customer->id,
                'branch_id' => $branch->id,
                'seller_id' => $seller->id,
                'invoice_number' => trim((string) $payload['invoice_number']),
                'invoice_amount' => $invoiceAmount,
                'discount_percentage' => $promoCode->discount_value,
                'discount_amount' => $discountAmount,
                'redeemed_at' => now(),
            ])->load(['promoCode', 'customer', 'branch', 'seller']);
        });
    }

    private function sellerBranchForPromo(User $seller, PromoCode $promoCode, bool $locked = false): Branch
    {
        $query = $seller->branches()->where('branches.is_active', true);
        if ($locked) {
            $query->lockForUpdate();
        }

        $branches = $query->get();
        if ($branches->isEmpty()) {
            throw ValidationException::withMessages(['branch' => 'لا يوجد فرع نشط مرتبط بحساب البائع.']);
        }

        $branch = $branches->first(fn (Branch $branch) => $promoCode->branches->contains('id', $branch->id));
        if (! $branch) {
            throw ValidationException::withMessages(['branch' => 'هذا الكود غير مفعل في فرع البائع الحالي.']);
        }

        return $branch;
    }

    private function assertTimeWindow(PromoCode $promoCode): void
    {
        $now = Carbon::now();

        if ($promoCode->starts_at && $promoCode->starts_at->gt($now)) {
            throw ValidationException::withMessages(['code' => 'الكود لم يبدأ بعد.']);
        }

        if ($promoCode->expires_at && $promoCode->expires_at->lt($now)) {
            throw ValidationException::withMessages(['code' => 'انتهت صلاحية الكود.']);
        }
    }

    private function assertUsageLimits(PromoCode $promoCode, Customer $customer): void
    {
        if ($promoCode->max_total_uses !== null && $promoCode->redemptions()->count() >= $promoCode->max_total_uses) {
            throw ValidationException::withMessages(['code' => 'تم تجاوز الحد الأقصى لاستخدام الكود.']);
        }

        $customerUses = $promoCode->redemptions()->where('customer_id', $customer->id)->count();
        if ($customerUses >= $promoCode->max_uses_per_customer) {
            throw ValidationException::withMessages(['code' => 'تم استخدام هذا الكود من قبل لهذا العميل.']);
        }
    }

    private function remainingUses(PromoCode $promoCode, Customer $customer): array
    {
        $customerUses = $promoCode->redemptions()->where('customer_id', $customer->id)->count();

        return [
            'total' => $promoCode->max_total_uses === null ? null : max(0, $promoCode->max_total_uses - $promoCode->redemptions()->count()),
            'customer' => max(0, $promoCode->max_uses_per_customer - $customerUses),
        ];
    }

    private function promoPayload(PromoCode $promoCode, Customer $customer, Branch $branch): array
    {
        return [
            'id' => $promoCode->id,
            'code' => $promoCode->code,
            'title' => $promoCode->title,
            'discount_value' => (float) $promoCode->discount_value,
            'max_invoice_amount' => $promoCode->max_invoice_amount === null ? null : (float) $promoCode->max_invoice_amount,
            'max_discount_amount' => $promoCode->max_discount_amount === null ? null : (float) $promoCode->max_discount_amount,
            'expires_at' => $promoCode->expires_at?->toDateTimeString(),
            'current_customer_uses' => $promoCode->redemptions()->where('customer_id', $customer->id)->count(),
            'current_branch' => $branch->name,
        ];
    }

    private function customerPayload(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'city' => $customer->city,
        ];
    }

    private function branchPayload(Branch $branch): array
    {
        return [
            'id' => $branch->id,
            'name' => $branch->name,
            'code' => $branch->code,
        ];
    }
}
