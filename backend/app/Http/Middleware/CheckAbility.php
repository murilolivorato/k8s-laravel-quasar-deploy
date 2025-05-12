<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAbility
{
    public function handle(Request $request, Closure $next, $ability)
    {
        if (!$request->user()->tokenCan($ability)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        return $next($request);
    }
} 