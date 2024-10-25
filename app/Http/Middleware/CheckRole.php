<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    public function handle($request, Closure $next, $role)
    {
        try {
           // dd($role);
            // Check if the user is authenticated
            $user = $request->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized A'], 403);
            }

            // Debugging code to check user roles
            $userRoles = $user->roles->pluck('name')->toArray();
            Log::info('User Roles:', ['roles' => $userRoles]);

            // Check if the authenticated user has at least one of the required roles
            if (!$user->hasAnyRole(explode('|', $role))) {
                Log::info('Unauthorized B: User does not have the required role');
                return response()->json(['message' => 'Unauthorized B'], 403);
            }

            return $next($request);
        } catch (\Exception $e) {
            Log::error('CheckRole Middleware Error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'role' => $role,
                'user' => optional($user)->toArray(),
            ]);

            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }
}
