<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the public /api/external/* routes with a shared bearer token stored on each
 * user as `external_api_token`. On success, the matched user is attached to the request
 * via `$request->attributes->set('external_user', $user)`.
 */
class VerifyExternalApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');
        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
            return response()->json(['error' => 'missing_bearer_token'], 401);
        }

        $token = trim($m[1]);
        $user = User::where('external_api_token', $token)->where('is_active', true)->first();
        if (!$user) {
            return response()->json(['error' => 'invalid_token'], 401);
        }

        $request->attributes->set('external_user', $user);
        return $next($request);
    }
}
