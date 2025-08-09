<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle($request, Closure $next, ...$roles): Response
    {
        // Debug: Log thông tin user và role
        Log::info('CheckRole middleware - User: ' . auth()->user()->name . ' Role: ' . auth()->user()->role . ' Required roles: ' . implode(',', $roles));
        
        if (!in_array(auth()->user()->role, $roles)) {
            Log::warning('Access denied - User role: ' . auth()->user()->role . ' Required: ' . implode(',', $roles));
            abort(403, 'Không có quyền truy cập.');
        }

        return $next($request);
    }
}
