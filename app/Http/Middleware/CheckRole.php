<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
{
    if (!Auth::check()) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $user = Auth::user();
    $userRoles = $user->roles()->pluck('slug')->toArray();
    \Log::debug('Authenticated user roles: ' . implode(',', $userRoles));
    
    foreach ($roles as $role) {
        if ($user->hasRole($role)) {
            return $next($request);
        }
    }
    
    return response()->json(['message' => 'You do not have permission to access this resource'], 403);
}

}