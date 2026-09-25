<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserNotBanned
{
    /**
     * Kick out authenticated-but-banned users so they cannot keep using
     * the site after being banned mid-session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isBanned()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Tài khoản của bạn đã bị khóa.'.($user->ban_reason ? ' Lý do: '.$user->ban_reason : '')]);
        }

        return $next($request);
    }
}
