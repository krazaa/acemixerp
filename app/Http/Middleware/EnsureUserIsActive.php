<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && ! $user->isActive()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $msg = 'Your account is '.$user->status->label().'.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg], 403)
                : redirect()->route('login')->withErrors(['email' => $msg]);
        }

        return $next($request);
    }
}
