<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-3xl border border-destructive/20 bg-destructive px-6 py-3 text-sm font-semibold text-destructive-foreground shadow-2xl backdrop-blur-xl transition hover:bg-destructive/90 focus:outline-none focus:ring-2 focus:ring-destructive/40 focus:ring-offset-2 focus:ring-offset-background disabled:opacity-50']) }}>
    {{ $slot }}
</button>
