<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class RoleKecamatanMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = JWTAuth::parseToken()->authenticate();
        $role = Role::where('name', 'kecamatan')->firstOrFail();

        if(!$user || $user->role->name !== $role->name) {
            return response()->json([
                'success' => false,
                'message' => 'User bukan Admin Kecamatan'
            ], 401);
        }
        return $next($request);
    }
}
