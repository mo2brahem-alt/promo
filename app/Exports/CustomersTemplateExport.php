<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomersTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['name', 'phone', 'email', 'city', 'notes'];
    }

    public function array(): array
    {
        return [];
    }
}
