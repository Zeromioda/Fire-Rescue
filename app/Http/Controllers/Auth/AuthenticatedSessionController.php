<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\OtpLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        // 1. Authenticate credentials via LoginRequest
        $request->authenticate();

        $user = Auth::user();

        // OTP can be switched off with LOGIN_OTP_ENABLED=false (e.g. if mail is down),
        // and is only required for Admin accounts
        if (! config('auth.login_otp') || ! $user->hasRole('Admin')) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard', absolute: false));
        }

        // 2. Temporarily log the user out so they can't access protected routes yet
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $limiter = OtpLimiter::for('login', $user->email);

        // 3. Too many codes requested: block until the lockout window passes
        if ($limiter->lockedOut()) {
            return redirect()->route('login')
                ->onlyInput('email')
                ->withErrors(['email' => $limiter->blockedMessage()]);
        }

        // 4. Store the user ID temporarily in the new session for verification
        $request->session()->put('auth.login_user_id', $user->id);

        // 5. A code was sent seconds ago: reuse it instead of sending another
        if ($limiter->availableIn() > 0) {
            return redirect()->route('login.otp.verify.view')
                ->with('success', 'A verification code was already sent to your email.');
        }

        // 6. Generate and send a fresh OTP
        if (! LoginOtpVerificationController::sendCode($user)) {
            $request->session()->forget('auth.login_user_id');

            return redirect()->route('login')
                ->withErrors(['email' => 'We could not send your verification code. Please try again or contact the administrator.']);
        }

        // 7. Redirect to the OTP verification screen with success message
        return redirect()->route('login.otp.verify.view')
                         ->with('success', 'A verification code has been sent to your email.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->input('reason') === 'idle') {
            return redirect()->route('login')
                ->with('status', 'You were logged out after '.intdiv(config('auth.idle_timeout'), 60).' minutes of inactivity.');
        }

        return redirect('/');
    }
}