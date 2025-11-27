<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Cek apakah user login DAN memiliki role 'super_admin'
        if ($request->user() && $request->user()->role === 'super_admin') {
            return $next($request);
        }

        // Jika tidak, tolak akses (403 Forbidden)
        abort(403, 'AKSES DITOLAK: Halaman ini khusus untuk Super Admin.');
    }
}
