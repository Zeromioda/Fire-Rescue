<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Mail\LoginOtpMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
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

        // 2. Generate a 6-digit OTP and set 10-minute expiration
        $otp = rand(100000, 999999);
        $user->login_otp = $otp;
        $user->login_otp_expires_at = now()->addMinutes(10);
        $user->save();

        // 3. Send the OTP email via Resend
        Mail::to($user->email)->send(new LoginOtpMail($otp));

        // 4. Temporarily log the user out so they can't access protected routes yet
        Auth::guard('web')->logout();
        
        // Invalidate session securely and regenerate token
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 5. Store the user ID temporarily in the new session for verification
        $request->session()->put('auth.login_user_id', $user->id);

        // 6. Redirect to the OTP verification screen with success message
        return redirect()->route('login.otp.verify.view')
                         ->with('success', 'A verification code has been sent to your email.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}