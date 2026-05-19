<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchRequest;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));

        return Branch::with('users:id,name,email,phone,role')
            ->withCount(['users as sellers_count' => fn ($query) => $query->where('role', 'seller')])
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate((int) $request->query('per_page', 10));
    }

    public function store(BranchRequest $request)
    {
        $data = $request->validated();
        $sellerIds = $data['seller_ids'] ?? [];
        unset($data['seller_ids']);

        $branch = Branch::create($data);
        $branch->users()->sync($sellerIds);

        return response()->json($branch->load('users:id,name,email,phone,role'), 201);
    }

    public function update(BranchRequest $request, Branch $branch)
    {
        $data = $request->validated();
        $sellerIds = $data['seller_ids'] ?? [];
        unset($data['seller_ids']);

        $branch->update($data);
        $branch->users()->sync($sellerIds);

        return response()->json($branch->fresh()->load('users:id,name,email,phone,role'));
    }

    public function toggle(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $branch->update(['is_active' => $data['is_active']]);

        return response()->json($branch->fresh()->load('users:id,name,email,phone,role'));
    }

    public function destroy(Branch $branch)
    {
        if ($branch->redemptions()->exists()) {
            return response()->json(['message' => 'لا يمكن حذف فرع لديه استخدامات مسجلة.'], 422);
        }

        $branch->delete();

        return response()->json(['message' => 'تم حذف الفرع.']);
    }
}
