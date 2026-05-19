<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PhoneNormalizer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));

        return User::with('branches:id,name,code')
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', '%'.PhoneNormalizer::normalize($search).'%');
            }))
            ->when($request->query('role'), fn ($query, $role) => $query->where('role', $role))
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN)))
            ->when($request->query('branch_id'), fn ($query, $branchId) => $query->whereHas('branches', fn ($branchQuery) => $branchQuery->where('branches.id', $branchId)))
            ->orderBy('name')
            ->paginate((int) $request->query('per_page', 15), ['id', 'name', 'email', 'phone', 'role', 'is_active', 'created_at']);
    }

    public function store(Request $request)
    {
        $data = $this->validatedUserData($request);
        $branchIds = $data['branch_ids'] ?? [];
        unset($data['branch_ids'], $data['password_confirmation']);

        if (isset($data['phone'])) {
            $data['phone'] = PhoneNormalizer::normalize($data['phone']);
        }

        $user = User::create($data);
        $user->branches()->sync($data['role'] === User::ROLE_SELLER ? $branchIds : []);

        return response()->json($user->load('branches:id,name,code'), 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validatedUserData($request, $user);
        $branchIds = $data['branch_ids'] ?? [];
        unset($data['branch_ids'], $data['password'], $data['password_confirmation']);

        if (isset($data['phone'])) {
            $data['phone'] = PhoneNormalizer::normalize($data['phone']);
        }

        $this->assertCanChangeRoleOrStatus($user, $data['role'] ?? $user->role, $data['is_active'] ?? $user->is_active);

        $user->update($data);
        $user->branches()->sync($user->role === User::ROLE_SELLER ? $branchIds : []);

        return response()->json($user->fresh()->load('branches:id,name,code'));
    }

    public function toggle(Request $request, User $user)
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $this->assertCanChangeRoleOrStatus($user, $user->role, $data['is_active']);
        $user->update(['is_active' => $data['is_active']]);

        return response()->json($user->fresh()->load('branches:id,name,code'));
    }

    public function changePassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user->update(['password' => $data['password']]);

        return response()->json(['message' => 'تم تغيير كلمة المرور.']);
    }

    private function validatedUserData(Request $request, ?User $user = null): array
    {
        $isUpdate = $user !== null;

        if ($request->has('phone')) {
            $request->merge([
                'phone' => PhoneNormalizer::normalize($request->input('phone')),
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user?->id)],
            'password' => [$isUpdate ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
            'role' => ['required', Rule::in(User::ROLES)],
            'is_active' => ['boolean'],
            'branch_ids' => [Rule::requiredIf(fn () => $request->input('role') === User::ROLE_SELLER), 'array'],
            'branch_ids.*' => ['integer', 'exists:branches,id'],
        ]);

        if (isset($data['phone'])) {
            $data['phone'] = PhoneNormalizer::normalize($data['phone']);
        }

        if (($data['role'] ?? null) !== User::ROLE_SELLER) {
            $data['branch_ids'] = [];
        }

        return $data;
    }

    private function assertCanChangeRoleOrStatus(User $user, string $newRole, bool $newStatus): void
    {
        if ($user->email === 'superadmin@example.com' && ($newRole !== User::ROLE_SUPER_ADMIN || ! $newStatus)) {
            throw ValidationException::withMessages([
                'user' => 'لا يمكن تعطيل أو تغيير دور حساب Super Admin الأساسي.',
            ]);
        }

        if ($user->isSuperAdmin() && ($newRole !== User::ROLE_SUPER_ADMIN || ! $newStatus)) {
            $activeSuperAdmins = User::where('role', User::ROLE_SUPER_ADMIN)
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($activeSuperAdmins === 0) {
                throw ValidationException::withMessages([
                    'user' => 'لا يمكن تعطيل أو تغيير آخر Super Admin نشط.',
                ]);
            }
        }
    }
}
