<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Login Verification Code</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f5; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 8px; border: 1px solid #e4e4e7;">
        <h2 style="color: #18181b; margin-top: 0;">Login Verification</h2>
        <p style="color: #52525b; font-size: 14px;">You are attempting to log into your BFAD 178 account. Please use the following one-time verification code:</p>
        
        <div style="margin: 24px 0; padding: 16px; background: #f4f4f5; border-radius: 6px; text-align: center;">
            <span style="font-size: 32px; font-weight: 900; letter-spacing: 6px; color: #e11d48;">{{ $otp }}</span>
        </div>

        <p style="color: #71717a; font-size: 12px;">This code will expire in {{ config('auth.otp.expires_minutes') }} minutes. If you did not request this code, please ignore this email.</p>
    </div>
</body>
</html>