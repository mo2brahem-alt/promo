<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\PromoCode;
use App\Models\PromoCodeRedemption;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'promo_code_id' => ['nullable', 'integer', 'exists:promo_codes,id'],
            'seller_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $from = isset($filters['from'])
            ? Carbon::parse($filters['from'])->startOfDay()
            : now()->subDays(29)->startOfDay();

        $to = isset($filters['to'])
            ? Carbon::parse($filters['to'])->endOfDay()
            : now()->endOfDay();

        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        if ($user->hasRole('seller')) {
            $sellerRedemptions = $this->filteredRedemptions($from, $to, $filters)
                ->where('seller_id', $user->id);

            return response()->json([
                'role' => $user->role,
                'filters' => [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                ],
                'charts' => [
                    'daily_redemptions' => $this->dailyRedemptions(clone $sellerRedemptions),
                    'promo_usage' => $this->promoUsage(clone $sellerRedemptions),
                ],
                'summary' => $this->redemptionSummary(clone $sellerRedemptions),
                'recent_redemptions' => (clone $sellerRedemptions)->with(['promoCode:id,code,title', 'customer:id,name,phone', 'branch:id,name,code'])
                    ->where('seller_id', $user->id)
                    ->latest('redeemed_at')
                    ->limit(10)
                    ->get(),
            ]);
        }

        $filteredRedemptions = $this->filteredRedemptions($from, $to, $filters);

        return response()->json([
            'role' => $user->role,
            'customers_count' => Customer::count(),
            'branches_count' => Branch::count(),
            'promo_codes_count' => PromoCode::count(),
            'active_codes_count' => PromoCode::where('is_active', true)->count(),
            'expired_codes_count' => PromoCode::whereNotNull('expires_at')->where('expires_at', '<', now())->count(),
            'today_redemptions_count' => PromoCodeRedemption::whereDate('redeemed_at', today())->count(),
            'today_discount_amount' => round((float) PromoCodeRedemption::whereDate('redeemed_at', today())->sum('discount_amount'), 2),
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'filter_options' => [
                'branches' => Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
                'promo_codes' => PromoCode::query()->orderBy('code')->get(['id', 'code', 'title']),
                'sellers' => User::query()->where('role', User::ROLE_SELLER)->orderBy('name')->get(['id', 'name']),
            ],
            'charts' => [
                'daily_redemptions' => $this->dailyRedemptions(clone $filteredRedemptions),
                'branch_usage' => $this->branchUsage(clone $filteredRedemptions),
                'promo_usage' => $this->promoUsage(clone $filteredRedemptions),
                'seller_usage' => $this->sellerUsage(clone $filteredRedemptions),
                'code_status' => $this->codeStatus(),
            ],
            'summary' => $this->redemptionSummary(clone $filteredRedemptions),
            'recent_redemptions' => (clone $filteredRedemptions)->with(['promoCode:id,code,title', 'customer:id,name,phone', 'branch:id,name,code', 'seller:id,name'])
                ->latest('redeemed_at')
                ->limit(10)
                ->get(),
        ]);
    }

    private function filteredRedemptions(Carbon $from, Carbon $to, array $filters)
    {
        return PromoCodeRedemption::query()
            ->whereBetween('redeemed_at', [$from, $to])
            ->when($filters['branch_id'] ?? null, fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->when($filters['promo_code_id'] ?? null, fn ($query, $promoCodeId) => $query->where('promo_code_id', $promoCodeId))
            ->when($filters['seller_id'] ?? null, fn ($query, $sellerId) => $query->where('seller_id', $sellerId));
    }

    private function redemptionSummary($query): array
    {
        $row = $query
            ->selectRaw('COUNT(*) as total_redemptions')
            ->selectRaw('COALESCE(SUM(invoice_amount), 0) as total_invoice_amount')
            ->selectRaw('COALESCE(SUM(discount_amount), 0) as total_discount_amount')
            ->selectRaw('COALESCE(AVG(discount_amount), 0) as average_discount_amount')
            ->selectRaw('COUNT(DISTINCT customer_id) as unique_customers')
            ->first();

        return [
            'total_redemptions' => (int) $row->total_redemptions,
            'total_invoice_amount' => round((float) $row->total_invoice_amount, 2),
            'total_discount_amount' => round((float) $row->total_discount_amount, 2),
            'average_discount_amount' => round((float) $row->average_discount_amount, 2),
            'unique_customers' => (int) $row->unique_customers,
        ];
    }

    private function dailyRedemptions($query)
    {
        return $query
            ->selectRaw('DATE(redeemed_at) as label')
            ->selectRaw('COUNT(*) as redemptions')
            ->selectRaw('COALESCE(SUM(invoice_amount), 0) as invoice_amount')
            ->selectRaw('COALESCE(SUM(discount_amount), 0) as discount_amount')
            ->groupBy(DB::raw('DATE(redeemed_at)'))
            ->orderBy('label')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label,
                'value' => (int) $row->redemptions,
                'invoice_amount' => round((float) $row->invoice_amount, 2),
                'discount_amount' => round((float) $row->discount_amount, 2),
            ]);
    }

    private function branchUsage($query)
    {
        return $query
            ->select('branch_id')
            ->selectRaw('COUNT(*) as value')
            ->selectRaw('COALESCE(SUM(discount_amount), 0) as discount_amount')
            ->with('branch:id,name,code')
            ->groupBy('branch_id')
            ->orderByDesc('value')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->branch?->name ?? 'غير محدد',
                'value' => (int) $row->value,
                'discount_amount' => round((float) $row->discount_amount, 2),
            ]);
    }

    private function promoUsage($query)
    {
        return $query
            ->select('promo_code_id')
            ->selectRaw('COUNT(*) as value')
            ->selectRaw('COALESCE(SUM(discount_amount), 0) as discount_amount')
            ->with('promoCode:id,code,title')
            ->groupBy('promo_code_id')
            ->orderByDesc('value')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->promoCode?->code ?? 'غير محدد',
                'value' => (int) $row->value,
                'discount_amount' => round((float) $row->discount_amount, 2),
            ]);
    }

    private function sellerUsage($query)
    {
        return $query
            ->select('seller_id')
            ->selectRaw('COUNT(*) as value')
            ->selectRaw('COALESCE(SUM(discount_amount), 0) as discount_amount')
            ->with('seller:id,name')
            ->groupBy('seller_id')
            ->orderByDesc('value')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->seller?->name ?? 'غير محدد',
                'value' => (int) $row->value,
                'discount_amount' => round((float) $row->discount_amount, 2),
            ]);
    }

    private function codeStatus(): array
    {
        return [
            ['label' => 'نشط', 'value' => PromoCode::where('is_active', true)->count()],
            ['label' => 'متوقف', 'value' => PromoCode::where('is_active', false)->count()],
            ['label' => 'منتهي', 'value' => PromoCode::whereNotNull('expires_at')->where('expires_at', '<', now())->count()],
        ];
    }
}
