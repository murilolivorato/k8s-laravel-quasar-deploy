<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')->get();
        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'slug' => 'required|string|max:255|unique:roles',
            'description' => 'nullable|string'
        ]);

        $role = Role::create($validated);
        return response()->json($role, 201);
    }

    public function show(Role $role)
    {
        return response()->json($role->load('users'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles')->ignore($role->id)
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles')->ignore($role->id)
            ],
            'description' => 'nullable|string'
        ]);

        $role->update($validated);
        return response()->json($role);
    }

    public function destroy(Role $role)
    {
        // Check if role is assigned to any users
        if ($role->users()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete role that is assigned to users'
            ], 422);
        }

        $role->delete();
        return response()->json(null, 204);
    }
} 