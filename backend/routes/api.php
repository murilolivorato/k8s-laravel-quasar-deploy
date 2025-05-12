<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\AnalyticsController;

// Public routes (no auth required)
Route::post('/login', [LoginController::class, 'login']);
Route::post('/register', [RegisterController::class, 'register']);

// Protected routes (auth required)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/check-auth', [LoginController::class, 'checkAuth']);

    // Auth routes
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/me', [LoginController::class, 'me']);
    Route::post('/refresh-token', [LoginController::class, 'refreshToken']);

    // Example of a protected route with ability check
    Route::get('/protected-resource', function (Request $request) {
        if ($request->user()->tokenCan('read')) {
            return response()->json(['message' => 'Access granted']);
        }
        return response()->json(['message' => 'Access denied'], 403);
    });

    // User routes
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
    Route::put('/users/{user}/toggle-status', [UserController::class, 'toggleStatus']);

    // Role routes
    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/roles/{role}', [RoleController::class, 'show']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{role}', [RoleController::class, 'update']);
    Route::delete('/roles/{role}', [RoleController::class, 'destroy']);

    // Analytics routes
    Route::get('/analytics/dashboard', [AnalyticsController::class, 'getDashboardStats']);
}); 