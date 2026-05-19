<?php

namespace App\Imports;

use App\Models\Customer;
use App\Services\PhoneNormalizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CustomersImport implements ToCollection, WithHeadingRow
{
    public array $errors = [];
    public int $totalRows = 0;
    public int $created = 0;
    public int $updated = 0;
    public int $skipped = 0;
    public int $failed = 0;

    public function __construct(
        private readonly string $duplicateStrategy,
        private readonly ?int $uploadedBy,
    ) {
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $data = [
                'name' => trim((string) ($row['name'] ?? '')),
                'phone' => PhoneNormalizer::normalize((string) ($row['phone'] ?? '')),
                'email' => trim((string) ($row['email'] ?? '')) ?: null,
                'city' => trim((string) ($row['city'] ?? '')) ?: null,
                'notes' => trim((string) ($row['notes'] ?? '')) ?: null,
            ];

            if ($data['name'] === '' && $data['phone'] === '') {
                continue;
            }

            $this->totalRows++;
            $rowNumber = $index + 2;

            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
                'phone' => ['required', 'string', 'max:30'],
                'email' => ['nullable', 'email', 'max:255'],
                'city' => ['nullable', 'string', 'max:255'],
                'notes' => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                $this->failed++;
                $this->errors[] = [
                    'row' => $rowNumber,
                    'messages' => $validator->errors()->all(),
                ];
                continue;
            }

            $customer = Customer::where('phone', $data['phone'])->first();
            if ($customer && $this->duplicateStrategy === 'skip') {
                $this->skipped++;
                continue;
            }

            if ($customer) {
                $customer->update($data);
                $this->updated++;
                continue;
            }

            $unique = Validator::make($data, [
                'phone' => [Rule::unique('customers', 'phone')],
            ]);

            if ($unique->fails()) {
                $this->failed++;
                $this->errors[] = [
                    'row' => $rowNumber,
                    'messages' => ['رقم الجوال مكرر.'],
                ];
                continue;
            }

            Customer::create($data + [
                'is_active' => true,
                'created_by' => $this->uploadedBy,
            ]);

            $this->created++;
        }
    }
}
