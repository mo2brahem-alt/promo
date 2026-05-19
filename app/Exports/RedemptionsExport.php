<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RedemptionsExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $rows)
    {
    }

    public function headings(): array
    {
        return [
            'promo_code',
            'customer',
            'customer_phone',
            'branch',
            'seller',
            'invoice_number',
            'invoice_amount',
            'discount_percentage',
            'discount_amount',
            'redeemed_at',
        ];
    }

    public function collection(): Collection
    {
        return $this->rows->map(fn ($row) => [
            $row->promoCode?->code,
            $row->customer?->name,
            $row->customer?->phone,
            $row->branch?->name,
            $row->seller?->name,
            $row->invoice_number,
            $row->invoice_amount,
            $row->discount_percentage,
            $row->discount_amount,
            $row->redeemed_at?->toDateTimeString(),
        ]);
    }
}
