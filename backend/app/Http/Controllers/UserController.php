<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Display a listing of users with optional filters
     */
    public function index(Request $request)
    {
        try {
            $query = User::with('roles');

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            // Filter by role
            if ($request->has('role_id')) {
                $query->whereHas('roles', function($q) use ($request) {
                    $q->where('roles.id', $request->role_id);
                });
            }

            // Filter by status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Sorting
            $sortField = $request->input('sort_by', 'name');
            $sortDirection = $request->input('sort_direction', 'asc');
            $query->orderBy($sortField, $sortDirection);

            // Pagination
            $perPage = $request->input('per_page', 10);
            $users = $query->paginate($perPage);

            return response()->json($users);
        } catch (\Exception $e) {
            Log::error('Error fetching users: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching users'], 500);
        }
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => ['required', 'confirmed', Password::min(8)],
                'phone' => 'nullable|string|max:20',
                'roles' => 'required|array',
                'roles.*' => 'exists:roles,id',
                'is_active' => 'boolean',
                'notes' => 'nullable|string',
                'avatar' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'is_active' => $request->is_active ?? true,
                'notes' => $request->notes,
                'avatar' => $request->avatar
            ]);

            $user->roles()->sync($request->roles);

            DB::commit();

            return response()->json($user->load('roles'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating user: ' . $e->getMessage());
            return response()->json(['message' => 'Error creating user'], 500);
        }
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        try {
            return response()->json($user->load('roles'));
        } catch (\Exception $e) {
            Log::error('Error fetching user: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching user'], 500);
        }
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($user->id)
                ],
                'password' => ['nullable', 'confirmed', Password::min(8)],
                'phone' => 'nullable|string|max:20',
                'roles' => 'required|array',
                'roles.*' => 'exists:roles,id',
                'is_active' => 'boolean',
                'notes' => 'nullable|string',
                'avatar' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'is_active' => $request->is_active,
                'notes' => $request->notes,
                'avatar' => $request->avatar
            ];

            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            $user->update($userData);
            $user->roles()->sync($request->roles);

            DB::commit();

            return response()->json($user->load('roles'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating user: ' . $e->getMessage());
            return response()->json(['message' => 'Error updating user'], 500);
        }
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        try {
            DB::beginTransaction();

            // Prevent self-deletion
            if ($user->id === auth()->id()) {
                return response()->json([
                    'message' => 'You cannot delete your own account'
                ], 403);
            }

            // Check if user has any important data or relationships
            // Add your business logic here

            $user->roles()->detach();
            $user->delete();

            DB::commit();

            return response()->json(null, 204);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting user: ' . $e->getMessage());
            return response()->json(['message' => 'Error deleting user'], 500);
        }
    }

    /**
     * Toggle user status (active/inactive)
     */
    public function updateStatus(User $user)
    {
        try {
            DB::beginTransaction();

            // Prevent self-deactivation
            if ($user->id === auth()->id()) {
                return response()->json([
                    'message' => 'You cannot deactivate your own account'
                ], 403);
            }

            $user->update([
                'is_active' => !$user->is_active
            ]);

            DB::commit();

            return response()->json($user->load('roles'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating user status: ' . $e->getMessage());
            return response()->json(['message' => 'Error updating user status'], 500);
        }
    }

    /**
     * Get current authenticated user
     */
    public function me()
    {
        try {
            return response()->json(auth()->user()->load('roles'));
        } catch (\Exception $e) {
            Log::error('Error fetching current user: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching current user'], 500);
        }
    }

    /**
     * Update user's avatar
     */
    public function updateAvatar(Request $request, User $user)
    {
        try {
            $validator = Validator::make($request->all(), [
                'avatar' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user->update([
                'avatar' => $request->avatar
            ]);

            return response()->json($user);
        } catch (\Exception $e) {
            Log::error('Error updating user avatar: ' . $e->getMessage());
            return response()->json(['message' => 'Error updating user avatar'], 500);
        }
    }

    /**
     * Update user's last login information
     */
    public function updateLastLogin(Request $request, User $user)
    {
        try {
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip()
            ]);

            return response()->json($user);
        } catch (\Exception $e) {
            Log::error('Error updating user last login: ' . $e->getMessage());
            return response()->json(['message' => 'Error updating user last login'], 500);
        }
    }

    /**
     * Bulk update users
     */
    public function bulkUpdate(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,id',
                'action' => 'required|in:activate,deactivate,delete,assign_roles',
                'role_ids' => 'required_if:action,assign_roles|array',
                'role_ids.*' => 'exists:roles,id'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $users = User::whereIn('id', $request->user_ids)
                        ->where('id', '!=', auth()->id())
                        ->get();

            foreach ($users as $user) {
                switch ($request->action) {
                    case 'activate':
                        $user->update(['is_active' => true]);
                        break;
                    case 'deactivate':
                        $user->update(['is_active' => false]);
                        break;
                    case 'delete':
                        $user->roles()->detach();
                        $user->delete();
                        break;
                    case 'assign_roles':
                        $user->roles()->sync($request->role_ids);
                        break;
                }
            }

            DB::commit();

            return response()->json(['message' => 'Bulk update successful']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in bulk update: ' . $e->getMessage());
            return response()->json(['message' => 'Error in bulk update'], 500);
        }
    }
} 