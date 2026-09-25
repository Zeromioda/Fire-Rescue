<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 rounded-3xl border border-border bg-card/95 px-6 py-3 text-sm font-semibold text-foreground shadow-2xl backdrop-blur-xl transition hover:bg-card-alt focus:outline-none focus:ring-2 focus:ring-ring/40 focus:ring-offset-2 focus:ring-offset-background disabled:opacity-50']) }}>
    {{ $slot }}
</button>
