<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\UserToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Custom, hand-rolled token authentication.
 *
 * 1. Reads AuthorizationKey or Authorization: Bearer header.
 * 2. Extracts plain text token.
 * 3. Looks up token hash in user_tokens table.
 * 4. Rejects with 401 if missing, unknown, or expired.
 * 5. Binds authenticated user to request and Auth manager.
 */
class AuthToken
{
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $plainTextToken = $request->header('AuthorizationKey') ?? $request->bearerToken();
        $plainRememberToken = $request->header('X-Remember-Token');

        $sevenDaysInSeconds = 7 * 24 * 60 * 60;

        if (empty($plainTextToken) && empty($plainRememberToken)) {
            return Response::error('Missing authentication token.', 401);
        }

        /** @var UserToken|null $token */
        $token = $plainTextToken
            ? UserToken::with('user')->where('token_hash', hash('sha256', $plainTextToken))->first()
            : null;

        $rememberToken = $plainRememberToken
            ? User::where('remembertoken', hash('sha256', $plainRememberToken))->first()
            : null;

        // Check if last_activity exists and the difference is greater than 7 days
        if ($rememberToken && $rememberToken->last_activity !== null) {
            if ((now()->timestamp - (int) $rememberToken->last_activity) > $sevenDaysInSeconds) {
                $rememberToken->update([
                    'remembertoken' => null,
                ]);
                return Response::error('Authentication token has expired.', 401);
            }
        }

        if (!$token && !$rememberToken) {
            return Response::error('Invalid authentication token.', 401);
        }

        if ($token && $token->isExpired()) {
            return Response::error('Authentication token has expired.', 401);
        }

        $user = $token?->user ?? $rememberToken;

        if (!$user) {
            return Response::error('Invalid authentication token.', 401);
        }

        Auth::setUser($user);
        $request->setUserResolver(fn() => $user);
        $request->attributes->set('current_token', $plainTextToken);


        return $next($request);
    }
}
