<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function getDashboardStats()
    {
        try {
            // Get total users count
            $totalUsers = User::count();
            
            // Get active users (last 30 days)
            $activeUsers = User::where('last_login_at', '>=', Carbon::now()->subDays(30))->count();
            
            // Get new users (last 30 days)
            $newUsers = User::where('created_at', '>=', Carbon::now()->subDays(30))->count();
            
            // Get role distribution
            $roleDistribution = Role::withCount('users')->get()->map(function ($role) {
                return [
                    'name' => $role->name,
                    'count' => $role->users_count
                ];
            });

            // Get user registration trend (last 7 days)
            $registrationTrend = User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Get login activity (last 7 days)
            $loginActivity = User::select(
                DB::raw('DATE(last_login_at) as date'),
                DB::raw('count(*) as count')
            )
                ->where('last_login_at', '>=', Carbon::now()->subDays(7))
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Get system health metrics
            $systemHealth = [
                'database_size' => DB::select('SELECT pg_database_size(current_database()) as size')[0]->size,
                'active_sessions' => DB::table('sessions')->count(),
                'last_backup' => Carbon::now()->subHours(rand(1, 24)), // Replace with actual backup time
                'server_load' => sys_getloadavg()[0] // Get server load average
            ];

            return response()->json([
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'new_users' => $newUsers,
                'role_distribution' => $roleDistribution,
                'registration_trend' => $registrationTrend,
                'login_activity' => $loginActivity,
                'system_health' => $systemHealth
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching analytics data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 