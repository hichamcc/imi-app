<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePayrollAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->canAccessPayroll()) {
            abort(403, 'Access denied. You do not have permission to use the HR / Payroll module.');
        }

        return $next($request);
    }
}
