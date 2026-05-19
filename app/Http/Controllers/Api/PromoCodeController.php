<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PromoCodeRequest;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PromoCodeController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));

        return PromoCode::with(['customers:id,name,phone', 'branches:id,name,code'])
            ->withCount('redemptions')
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate((int) $request->query('per_page', 10));
    }

    public function store(PromoCodeRequest $request)
    {
        $data = $request->validated();
        $customers = $data['customers'];
        $branches = $data['branches'];
        unset($data['customers'], $data['branches']);

        $data['code'] = strtoupper($data['code']);
        $data['created_by'] = $request->user()->id;
        $promoCode = PromoCode::create($data);
        $promoCode->customers()->sync($customers);
        $promoCode->branches()->sync($branches);

        return response()->json($promoCode->load(['customers:id,name,phone', 'branches:id,name,code']), 201);
    }

    public function update(PromoCodeRequest $request, PromoCode $promoCode)
    {
        $data = $request->validated();
        $customers = $data['customers'];
        $branches = $data['branches'];
        unset($data['customers'], $data['branches']);

        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        $promoCode->update($data);
        $promoCode->customers()->sync($customers);
        $promoCode->branches()->sync($branches);

        return response()->json($promoCode->fresh()->load(['customers:id,name,phone', 'branches:id,name,code'])->loadCount('redemptions'));
    }

    public function toggle(Request $request, PromoCode $promoCode)
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $promoCode->update(['is_active' => $data['is_active']]);

        return response()->json($promoCode->fresh()->load(['customers:id,name,phone', 'branches:id,name,code'])->loadCount('redemptions'));
    }

    public function destroy(PromoCode $promoCode)
    {
        if ($promoCode->redemptions()->exists()) {
            return response()->json(['message' => 'لا يمكن حذف كود له استخدامات مسجلة.'], 422);
        }

        $promoCode->delete();

        return response()->json(['message' => 'تم حذف البرومو كود.']);
    }

    public function generate()
    {
        do {
            $code = 'PROMO-'.Str::upper(Str::random(8));
        } while (PromoCode::where('code', $code)->exists());

        return response()->json(['code' => $code]);
    }
}
