<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Check if user is authenticated
        if (! auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = auth()->user();

        // Check if user has one of the required roles
        $userRole = $user->role ?? null;

        if (! $userRole || ! in_array($userRole, $roles)) {
            return response()->json([
                'message' => 'Unauthorized. Required roles: '.implode(', ', $roles),
            ], 403);
        }

        return $next($request);
    }
}
