<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\LoginOtpMail;
use App\Models\User;
use App\Services\OtpLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class LoginOtpVerificationController extends Controller
{
    /**
     * Generate a new login OTP for the user and email it.
     * Returns false if the email could not be sent.
     */
    public static function sendCode(User $user): bool
    {
        $otp = (string) random_int(100000, 999999);
        $user->login_otp = $otp;
        $user->login_otp_expires_at = now()->addMinutes(config('auth.otp.expires_minutes'));
        $user->save();

        try {
            Mail::to($user->email)->send(new LoginOtpMail($otp));
        } catch (\Throwable $e) {
            Log::error('Login OTP mail failed: '.$e->getMessage());

            return false;
        }

        OtpLimiter::for('login', $user->email)->hit();

        return true;
    }

    public function showVerifyForm(): View|RedirectResponse
    {
        // Ensure there is a pending user ID in session
        $user = User::find(session('auth.login_user_id'));

        if (! $user) {
            return redirect()->route('login');
        }

        $limiter = OtpLimiter::for('login', $user->email);

        return view('auth.verify-otp', [
            'expiresAt' => $user->login_otp_expires_at ? Carbon::parse($user->login_otp_expires_at) : null,
            'resendIn' => $limiter->availableIn(),
            'resendsLeft' => $limiter->resendsLeft(),
        ]);
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = User::find(session('auth.login_user_id'));

        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'Session expired. Please log in again.']);
        }

        $limiter = OtpLimiter::for('login', $user->email);

        if ($limiter->availableIn() > 0) {
            return back()->withErrors(['otp' => $limiter->blockedMessage()]);
        }

        if (! self::sendCode($user)) {
            return back()->withErrors(['otp' => 'We could not send a new code. Please try again later.']);
        }

        return back()->with('success', 'A new verification code has been sent to your email.');
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $userId = session('auth.login_user_id');

        if (!$userId) {
            return redirect()->route('login')->withErrors(['email' => 'Session expired. Please log in again.']);
        }

        $user = User::find($userId);

        if (!$user || !$user->login_otp || !hash_equals($user->login_otp, $request->otp)) {
            return back()->withErrors(['otp' => 'The provided verification code is invalid.']);
        }

        if ($user->login_otp_expires_at && now()->greaterThan($user->login_otp_expires_at)) {
            return back()->withErrors(['otp' => 'The verification code has expired. Please request a new code.']);
        }

        // Clear OTP fields and send limits
        $user->login_otp = null;
        $user->login_otp_expires_at = null;
        $user->save();
        OtpLimiter::for('login', $user->email)->clear();

        // Log the user in officially
        Auth::login($user);

        // Remove temporary session data
        session()->forget('auth.login_user_id');
        $request->session()->regenerate();

        // Redirect to intended dashboard
        return redirect()->intended(route('dashboard', absolute: false));
    }
}
