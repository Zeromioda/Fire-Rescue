@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-3xl border border-border bg-card/95 px-5 py-3 text-sm font-medium text-primary shadow-2xl backdrop-blur-xl']) }}>
        {{ $status }}
    </div>
@endif
