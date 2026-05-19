<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\PromoCode;
use App\Models\PromoCodeRedemption;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('seller')) {
            return response()->json([
                'role' => $user->role,
                'recent_redemptions' => PromoCodeRedemption::with(['promoCode:id,code,title', 'customer:id,name,phone', 'branch:id,name,code'])
                    ->where('seller_id', $user->id)
                    ->latest('redeemed_at')
                    ->limit(10)
                    ->get(),
            ]);
        }

        return response()->json([
            'role' => $user->role,
            'customers_count' => Customer::count(),
            'branches_count' => Branch::count(),
            'promo_codes_count' => PromoCode::count(),
            'active_codes_count' => PromoCode::where('is_active', true)->count(),
            'expired_codes_count' => PromoCode::whereNotNull('expires_at')->where('expires_at', '<', now())->count(),
            'today_redemptions_count' => PromoCodeRedemption::whereDate('redeemed_at', today())->count(),
            'today_discount_amount' => round((float) PromoCodeRedemption::whereDate('redeemed_at', today())->sum('discount_amount'), 2),
            'recent_redemptions' => PromoCodeRedemption::with(['promoCode:id,code,title', 'customer:id,name,phone', 'branch:id,name,code', 'seller:id,name'])
                ->latest('redeemed_at')
                ->limit(10)
                ->get(),
        ]);
    }
}
