<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiCredentials
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // Check if user is active
        if (!$request->user()->is_active) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Your account has been deactivated. Please contact an administrator.');
        }

        // Check if user has valid API credentials (skip for admins accessing user management)
        if (!$request->user()->hasValidApiCredentials() && !$this->isAdminRoute($request)) {
            return redirect()->route('contact-admin')->with('error', 'Please configure your API credentials to access the system.');
        }

        return $next($request);
    }

    /**
     * Check if this is an admin route that doesn't require API credentials
     */
    private function isAdminRoute(Request $request): bool
    {
        return $request->user()->isAdmin() && (
            $request->routeIs('admin.*') ||
            $request->routeIs('profile.*') ||
            $request->routeIs('dashboard')
        );
    }
}
