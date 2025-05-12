<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    /**
     * Handle user login
     */
    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'message' => 'Invalid credentials'
                ], 401);
            }

            $user = Auth::user();
            $user->last_login_at = now();
            $user->save();

            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => $user->load('roles'),
                'message' => 'Login successful'
            ]);

        } catch (\Exception $e) {
            \Log::error('Login failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Login failed'
            ], 500);
        }
    }

    /**
     * Handle user registration
     */
    public function register(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'phone' => 'nullable|string|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'is_active' => true, // You might want to change this based on your requirements
            ]);

            // Assign default role (e.g., 'viewer')
            $viewerRole = \App\Models\Role::where('slug', 'viewer')->first();
            if ($viewerRole) {
                $user->roles()->attach($viewerRole->id);
            }

            // Create token
            $token = $user->createToken('auth-token')->plainTextToken;

            // Log the registration
            Log::info('New user registered', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'token' => $token,
                'user' => $user->load('roles'),
                'message' => 'Registration successful'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'An error occurred during registration'
            ], 500);
        }
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request)
    {
        try {
            // Revoke the token
            $request->user()->currentAccessToken()->delete();

            // Log the logout
            Log::info('User logged out', [
                'user_id' => $request->user()->id
            ]);

            return response()->json([
                'message' => 'Logged out successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'An error occurred during logout'
            ], 500);
        }
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        try {
            $user = $request->user()->load('roles');
            
            return response()->json([
                'user' => $user
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user data', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'An error occurred while fetching user data'
            ], 500);
        }
    }

    /**
     * Refresh user token
     */
    public function refreshToken(Request $request)
    {
        try {
            $user = $request->user();
            
            // Revoke current token
            $request->user()->currentAccessToken()->delete();
            
            // Create new token
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => $user->load('roles')
            ]);

        } catch (\Exception $e) {
            Log::error('Token refresh failed', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'An error occurred while refreshing token'
            ], 500);
        }
    }

    /**
     * Check if user is authenticated
     */
    public function checkAuth(Request $request)
    {
        try {
            if (!$request->bearerToken()) {
                return response()->json([
                    'authenticated' => false,
                    'message' => 'No token provided'
                ], 401);
            }

            // Verify the token
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'authenticated' => false,
                    'message' => 'Invalid token'
                ], 401);
            }

            return response()->json([
                'authenticated' => true,
                'user' => $user->load('roles')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'authenticated' => false,
                'message' => 'Authentication check failed'
            ], 500);
        }
    }
} 