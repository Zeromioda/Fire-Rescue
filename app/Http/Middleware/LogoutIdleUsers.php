<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs out signed-in users whose last request was more than
 * auth.idle_timeout seconds ago. The browser-side idle timer in
 * components/idle-timeout handles the countdown card; this is the
 * backstop for closed/sleeping tabs.
 */
class LogoutIdleUsers
{
    public const KEEP_ALIVE_GRACE = 30;

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $lastActivity = $request->session()->get('last_activity_at');

            // The browser only pings keep-alive every KEEP_ALIVE_GRACE seconds while
            // the user is active, so allow that much slack before logging out here.
            if ($lastActivity && time() - $lastActivity > config('auth.idle_timeout') + self::KEEP_ALIVE_GRACE) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $message = 'You were logged out after '.intdiv(config('auth.idle_timeout'), 60).' minutes of inactivity.';

                if ($request->expectsJson()) {
                    return response()->json(['message' => $message], 401);
                }

                return redirect()->route('login')->with('status', $message);
            }

            $request->session()->put('last_activity_at', time());
        }

        return $next($request);
    }
}
