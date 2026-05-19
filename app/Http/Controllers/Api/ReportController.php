<?php

namespace App\Http\Controllers\Api;

use App\Exports\RedemptionsExport;
use App\Http\Controllers\Controller;
use App\Models\PromoCodeRedemption;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->filteredQuery($request);

        $summaryRows = (clone $query)->get();
        $rows = $query->latest('redeemed_at')->paginate((int) $request->query('per_page', 15));

        $topBranch = $summaryRows->groupBy(fn ($row) => $row->branch?->name ?: '')->sortByDesc->count()->keys()->first();
        $topCode = $summaryRows->groupBy(fn ($row) => $row->promoCode?->code ?: '')->sortByDesc->count()->keys()->first();

        return response()->json([
            'data' => $rows,
            'summary' => [
                'total_redemptions' => $summaryRows->count(),
                'total_invoice_amount' => round((float) $summaryRows->sum('invoice_amount'), 2),
                'total_discount_amount' => round((float) $summaryRows->sum('discount_amount'), 2),
                'top_branch' => $topBranch ?: null,
                'top_code' => $topCode ?: null,
            ],
        ]);
    }

    public function export(Request $request)
    {
        $rows = $this->filteredQuery($request)->latest('redeemed_at')->get();

        return Excel::download(new RedemptionsExport($rows), 'promo-redemptions.xlsx');
    }

    private function filteredQuery(Request $request)
    {
        return PromoCodeRedemption::with(['promoCode:id,code,title', 'customer:id,name,phone', 'branch:id,name,code', 'seller:id,name,email'])
            ->when($request->query('from'), fn ($query, $from) => $query->whereDate('redeemed_at', '>=', $from))
            ->when($request->query('to'), fn ($query, $to) => $query->whereDate('redeemed_at', '<=', $to))
            ->when($request->query('branch_id'), fn ($query, $id) => $query->where('branch_id', $id))
            ->when($request->query('customer_id'), fn ($query, $id) => $query->where('customer_id', $id))
            ->when($request->query('seller_id'), fn ($query, $id) => $query->where('seller_id', $id))
            ->when($request->query('promo_code_id'), fn ($query, $id) => $query->where('promo_code_id', $id))
            ->when($request->query('invoice_number'), fn ($query, $invoice) => $query->where('invoice_number', 'like', "%{$invoice}%"));
    }
}
