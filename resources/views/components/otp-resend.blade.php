@props(['action', 'expiresAt' => null, 'resendIn' => 0, 'resendsLeft' => 0])

{{--
    OTP expiry countdown + "Resend code" button with cooldown.
    The server (OtpLimiter) enforces the real limits; this only mirrors them.
--}}
<div x-data="{
        expiresIn: {{ $expiresAt ? max(0, (int) now()->diffInSeconds($expiresAt, false)) : 0 }},
        resendIn: {{ (int) $resendIn }},
        resendsLeft: {{ (int) $resendsLeft }},
        fmt(s) { return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); },
        init() {
            const start = Date.now(), exp = this.expiresIn, res = this.resendIn;
            setInterval(() => {
                const passed = Math.floor((Date.now() - start) / 1000);
                this.expiresIn = Math.max(0, exp - passed);
                this.resendIn = Math.max(0, res - passed);
            }, 1000);
        },
     }"
     class="space-y-3 text-center">

    <!-- Expiry -->
    <p class="text-sm font-medium">
        <template x-if="expiresIn > 0">
            <span class="text-muted-foreground">Code expires in <span class="text-foreground tabular-nums" x-text="fmt(expiresIn)"></span></span>
        </template>
        <template x-if="expiresIn <= 0">
            <span class="text-destructive">Your code has expired. Request a new one below.</span>
        </template>
    </p>

    <!-- Resend -->
    <form method="POST" action="{{ $action }}">
        @csrf
        <button type="submit"
                :disabled="resendIn > 0 || resendsLeft <= 0"
                class="text-sm font-medium text-primary hover:underline disabled:text-muted-foreground disabled:no-underline disabled:cursor-not-allowed transition cursor-pointer">
            <span x-show="resendsLeft <= 0">No resends left</span>
            <span x-show="resendsLeft > 0 && resendIn > 0">Resend code in <span class="tabular-nums" x-text="resendIn + 's'"></span></span>
            <span x-show="resendsLeft > 0 && resendIn <= 0">Resend code</span>
        </button>
    </form>

    <p class="text-xs text-muted-foreground">
        <span x-text="resendsLeft"></span> of {{ config('auth.otp.max_resends') }} resends left
    </p>
</div>
