<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ResourceController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->tokenCan('read')) {
            // User has 'read' ability
            return response()->json(['message' => 'Access granted']);
        }
        return response()->json(['message' => 'Access denied'], 403);
    }
} 