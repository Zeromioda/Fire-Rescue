<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\SendOtpNotification;
use App\Services\OtpLimiter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OtpPasswordResetController extends Controller
{
    public function showRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $limiter = OtpLimiter::for('password', $request->email);

        if ($limiter->lockedOut()) {
            return back()->withInput()->withErrors(['email' => $limiter->blockedMessage()]);
        }

        // Remember the email for the verify page (survives refreshes and resends)
        $request->session()->put('password_reset_email', $request->email);

        // A code was sent seconds ago: reuse it instead of sending another
        if ($limiter->availableIn() > 0) {
            return redirect()->route('password.otp.verify.form')
                ->with('status', 'A verification code was already sent to your email.');
        }

        if (! $this->sendCode($request->email)) {
            return back()->withInput()->withErrors(['email' => 'We could not send the verification code. Please try again later.']);
        }

        return redirect()->route('password.otp.verify.form')
            ->with('status', 'OTP verification code successfully sent to your email!');
    }

    public function resend(Request $request)
    {
        $email = $request->session()->get('password_reset_email');

        if (! $email) {
            return redirect()->route('password.otp.request');
        }

        $limiter = OtpLimiter::for('password', $email);

        if ($limiter->availableIn() > 0) {
            return back()->withErrors(['otp' => $limiter->blockedMessage()]);
        }

        if (! $this->sendCode($email)) {
            return back()->withErrors(['otp' => 'We could not send a new code. Please try again later.']);
        }

        return back()->with('status', 'A new verification code has been sent to your email.');
    }

    public function showVerifyForm(Request $request)
    {
        $email = $request->session()->get('password_reset_email');

        if (! $email) {
            return redirect()->route('password.otp.request');
        }

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();
        $limiter = OtpLimiter::for('password', $email);

        return view('auth.reset-password-otp', [
            'email' => $email,
            'expiresAt' => $record ? Carbon::parse($record->created_at)->addMinutes(config('auth.otp.expires_minutes')) : null,
            'resendIn' => $limiter->availableIn(),
            'resendsLeft' => $limiter->resendsLeft(),
        ]);
    }

    public function verifyAndReset(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'otp'      => 'required|digits:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        $expired = $record && now()->subMinutes(config('auth.otp.expires_minutes'))->greaterThan($record->created_at);

        if (!$record || $expired || !password_verify($request->otp, $record->token)) {
            return back()->withInput($request->only('email'))
                ->withErrors(['otp' => 'The provided OTP code is invalid or has expired.']);
        }

        // Reset user password (hashed by the User model cast)
        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => $request->password,
        ]);

        // Clear used OTP token and send limits
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        OtpLimiter::for('password', $request->email)->clear();
        $request->session()->forget('password_reset_email');

        return redirect()->route('login')->with('status', 'Password reset successfully! Please log in with your new password.');
    }

    /**
     * Generate a new reset OTP for the email and send it.
     * Returns false if the email could not be sent.
     */
    private function sendCode(string $email): bool
    {
        // Generate 6-digit OTP
        $otp = (string) random_int(100000, 999999);

        // Save to password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => bcrypt($otp),
                'created_at' => now()
            ]
        );

        try {
            User::where('email', $email)->first()->notify(new SendOtpNotification($otp));
        } catch (\Throwable $e) {
            Log::error('Password reset OTP mail failed: ' . $e->getMessage());

            return false;
        }

        OtpLimiter::for('password', $email)->hit();

        return true;
    }
}
