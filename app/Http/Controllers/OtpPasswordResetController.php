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
        return view('auth.forgot-password-otp');
    }

    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        // Generate 6-digit OTP
        $otp = rand(100000, 999999);

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
            // Attempt to send via real Gmail SMTP
            $user->notify(new SendOtpNotification($otp));
        } catch (\Exception $e) {
            // Log the exact error for debugging
            Log::error('SMTP Mail Error: ' . $e->getMessage());

            // Show the error on screen so you know why it didn't send
            return back()->withErrors(['email' => 'Could not send email. Error: ' . $e->getMessage()]);
        }

        return redirect()->route('password.otp.verify.form')
            ->with('email', $request->email)
            ->with('status', 'OTP verification code successfully sent to your email!');
    }

    public function showVerifyForm()
    {
        return view('auth.verify-otp');
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

        if (!$record || !password_verify($request->otp, $record->token)) {
            return back()->withErrors(['otp' => 'The provided OTP code is invalid or has expired.']);
        }

        // Reset user password
        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => bcrypt($request->password),
        ]);

        // Clear used OTP token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')->with('status', 'Password reset successfully! Please log in with your new password.');
    }
}