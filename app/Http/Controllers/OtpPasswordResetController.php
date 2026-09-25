<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\SendOtpNotification;
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

        // Generate 6-digit OTP
        $otp = (string) random_int(100000, 999999);

        // Save to password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => bcrypt($otp),
                'created_at' => now()
            ]
        );

        $user = User::where('email', $request->email)->first();

        try {
            $user->notify(new SendOtpNotification($otp));
        } catch (\Throwable $e) {
            Log::error('Password reset OTP mail failed: ' . $e->getMessage());

            return back()->withInput()->withErrors(['email' => 'We could not send the verification code. Please try again later.']);
        }

        return redirect()->route('password.otp.verify.form')
            ->with('email', $request->email)
            ->with('status', 'OTP verification code successfully sent to your email!');
    }

    public function showVerifyForm()
    {
        return view('auth.reset-password-otp', [
            'email' => session('email', old('email')),
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

        $expired = $record && now()->subMinutes(10)->greaterThan($record->created_at);

        if (!$record || $expired || !password_verify($request->otp, $record->token)) {
            return back()->withInput($request->only('email'))
                ->withErrors(['otp' => 'The provided OTP code is invalid or has expired.']);
        }

        // Reset user password (hashed by the User model cast)
        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => $request->password,
        ]);

        // Clear used OTP token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')->with('status', 'Password reset successfully! Please log in with your new password.');
    }
}
