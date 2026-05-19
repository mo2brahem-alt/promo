<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        return User::with('branches:id,name,code')
            ->when($request->query('role'), fn ($query, $role) => $query->where('role', $role))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'is_active']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', Rule::in(User::ROLES)],
            'is_active' => ['boolean'],
            'branch_ids' => ['array'],
            'branch_ids.*' => ['integer', 'exists:branches,id'],
        ]);

        $branchIds = $data['branch_ids'] ?? [];
        unset($data['branch_ids']);
        $user = User::create($data);
        $user->branches()->sync($branchIds);

        return response()->json($user->load('branches:id,name,code'), 201);
    }
}
