@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-3xl px-4 py-2 text-sm font-semibold bg-primary text-primary-foreground transition'
            : 'inline-flex items-center rounded-3xl px-4 py-2 text-sm font-medium text-muted-foreground hover:bg-card-alt hover:text-foreground transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
