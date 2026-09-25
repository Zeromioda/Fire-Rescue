<?php

namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;

/**
 * Enforces the OTP send rules: a cooldown between sends and a cap on
 * resends (the first send + max_resends) within the lockout window.
 */
class OtpLimiter
{
    private string $cooldownKey;

    private string $sendsKey;

    public function __construct(string $purpose, string $identifier)
    {
        $id = sha1(strtolower($identifier));
        $this->cooldownKey = "otp:cooldown:{$purpose}:{$id}";
        $this->sendsKey = "otp:sends:{$purpose}:{$id}";
    }

    public static function for(string $purpose, string $identifier): self
    {
        return new self($purpose, $identifier);
    }

    /** True when every allowed send (first + resends) has been used. */
    public function lockedOut(): bool
    {
        return RateLimiter::tooManyAttempts($this->sendsKey, $this->maxSends());
    }

    /** Seconds until another code may be sent (0 = can send now). */
    public function availableIn(): int
    {
        if ($this->lockedOut()) {
            return RateLimiter::availableIn($this->sendsKey);
        }

        if (RateLimiter::tooManyAttempts($this->cooldownKey, 1)) {
            return RateLimiter::availableIn($this->cooldownKey);
        }

        return 0;
    }

    public function resendsLeft(): int
    {
        return min(
            config('auth.otp.max_resends'),
            RateLimiter::remaining($this->sendsKey, $this->maxSends())
        );
    }

    /** Record that a code was just sent. */
    public function hit(): void
    {
        RateLimiter::hit($this->cooldownKey, config('auth.otp.resend_cooldown'));
        RateLimiter::hit($this->sendsKey, config('auth.otp.lockout_minutes') * 60);
    }

    public function clear(): void
    {
        RateLimiter::clear($this->cooldownKey);
        RateLimiter::clear($this->sendsKey);
    }

    /** Human-readable error for when a send is blocked. */
    public function blockedMessage(): string
    {
        $seconds = $this->availableIn();

        if ($this->lockedOut()) {
            $minutes = (int) ceil($seconds / 60);

            return "You've reached the maximum number of code requests. Please try again in {$minutes} minute(s).";
        }

        return "Please wait {$seconds} second(s) before requesting a new code.";
    }

    private function maxSends(): int
    {
        return 1 + config('auth.otp.max_resends');
    }
}
