<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PromoRedeemRequest;
use App\Http\Requests\PromoValidationRequest;
use App\Models\PromoCodeRedemption;
use App\Services\PromoCodeRedemptionService;
use Illuminate\Http\Request;

class SellerPromoController extends Controller
{
    public function validatePromo(PromoValidationRequest $request, PromoCodeRedemptionService $service)
    {
        return response()->json($service->validatePromo(
            $request->validated('code'),
            $request->validated('customer_phone'),
            $request->user(),
        ));
    }

    public function redeem(PromoRedeemRequest $request, PromoCodeRedemptionService $service)
    {
        $redemption = $service->redeem($request->validated(), $request->user());

        return response()->json([
            'message' => 'تم تفعيل البرومو بنجاح.',
            'redemption' => $redemption,
        ], 201);
    }

    public function myRedemptions(Request $request)
    {
        return PromoCodeRedemption::with(['promoCode:id,code,title', 'customer:id,name,phone', 'branch:id,name,code'])
            ->where('seller_id', $request->user()->id)
            ->latest('redeemed_at')
            ->limit(10)
            ->get();
    }
}
