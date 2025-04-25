<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            Log::error('Unauthorized: No authenticated user');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        $userRoles = $user->roles()->pluck('slug')->toArray();
        Log::debug('Authenticated user roles:', ['roles' => $userRoles, 'user_id' => $user->id]);
        Log::debug('Required roles:', ['roles' => $roles]);
        
        foreach ($roles as $role) {
            $hasRole = $user->hasRole($role);
            Log::debug('Checking role:', ['role' => $role, 'hasRole' => $hasRole]);
            if ($hasRole) {
                Log::info('Role check passed:', ['role' => $role]);
                return $next($request);
            }
        }
        
        Log::error('Role check failed for user:', ['user_id' => $user->id, 'required_roles' => $roles]);
        return response()->json(['message' => 'You do not have permission to access this resource'], 403);
    }
}