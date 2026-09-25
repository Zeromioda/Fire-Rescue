<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginOtpVerificationController extends Controller
{
    public function showVerifyForm(): View|RedirectResponse
    {
        // Ensure there is a pending user ID in session
        if (!session()->has('auth.login_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.verify-otp');
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

        $user = \App\Models\User::find($userId);

        if (!$user || $user->login_otp !== $request->otp) {
            return back()->withErrors(['otp' => 'The provided verification code is invalid.']);
        }

        if ($user->login_otp_expires_at && now()->greaterThan($user->login_otp_expires_at)) {
            return back()->withErrors(['otp' => 'The verification code has expired. Please log in again.']);
        }

        // Clear OTP fields
        $user->login_otp = null;
        $user->login_otp_expires_at = null;
        $user->save();

        // Log the user in officially
        Auth::login($user);

        // Remove temporary session data
        session()->forget('auth.login_user_id');
        $request->session()->regenerate();

        // Redirect to intended dashboard
        return redirect()->intended(route('dashboard', absolute: false));
    }
}