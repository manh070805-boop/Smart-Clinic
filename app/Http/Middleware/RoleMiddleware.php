<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $userRole = strtolower(trim((string) $user->role?->role_name));

        $allowedRoles = array_map(fn ($r) => strtolower(trim($r)), $roles);

        if (! in_array($userRole, $allowedRoles, true)) {
            abort(403, 'Bạn không có quyền truy cập khu vực này (Quy định phân quyền SC02).');
        }

        return $next($request);
    }
}
