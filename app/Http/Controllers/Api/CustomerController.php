<?php

namespace App\Http\Controllers\Api;

use App\Exports\CustomersTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerImportRequest;
use App\Http\Requests\CustomerRequest;
use App\Imports\CustomersImport;
use App\Models\Customer;
use App\Models\CustomerImport;
use App\Services\PhoneNormalizer;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));

        return Customer::query()
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', '%'.PhoneNormalizer::normalize($search).'%');
            }))
            ->latest()
            ->paginate((int) $request->query('per_page', 10));
    }

    public function store(CustomerRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        return response()->json(Customer::create($data), 201);
    }

    public function update(CustomerRequest $request, Customer $customer)
    {
        $data = $request->validated();
        $customer->update($data);

        return response()->json($customer->fresh());
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return response()->json(['message' => 'تم حذف العميل.']);
    }

    public function import(CustomerImportRequest $request)
    {
        $import = new CustomersImport($request->validated('duplicate_strategy'), $request->user()->id);
        Excel::import($import, $request->file('file'));

        CustomerImport::create([
            'file_name' => $request->file('file')->getClientOriginalName(),
            'total_rows' => $import->totalRows,
            'success_count' => $import->created + $import->updated,
            'failed_count' => $import->failed,
            'uploaded_by' => $request->user()->id,
        ]);

        return response()->json([
            'total_rows' => $import->totalRows,
            'created' => $import->created,
            'updated' => $import->updated,
            'skipped' => $import->skipped,
            'failed' => $import->failed,
            'errors' => $import->errors,
        ]);
    }

    public function template()
    {
        return Excel::download(new CustomersTemplateExport(), 'customers-template.xlsx');
    }
}
