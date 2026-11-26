<?php

namespace App\Http\Middleware;


use App\Models\PasswordReset;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;


class PasswordResetLinkExpired
{
    public function handle(Request $request, Closure $next): SymfonyResponse
    {

        $plainTextToken = $request->bearerToken() ?? $request->route('token');



        if (empty($plainTextToken)) {
            return Response::error('Missing Passwword Reset token of this link.', 403);
        }


        $passtoken = PasswordReset::where('token', $plainTextToken)->first();

        if (!$passtoken) {
            return Response::error('Invalid Passwword Reset token of this link.', 403);
        }

        if ($passtoken->isExpired()) {
            return Response::error('The Passwword Reset token of this link has been expired.', 403);
        }


        return $next($request);
    }
}

