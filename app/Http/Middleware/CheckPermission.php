<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permission): Response
    {
        if (!auth()->guard('sanctum')->check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        /** @var \App\Models\User $user */
        $user = auth()->guard('sanctum')->user();
        if (!$user->hasPermission($permission)) {
            return response()->json(['message' => 'Not has permission'], 401);
        }
        return $next($request);
    }
}
