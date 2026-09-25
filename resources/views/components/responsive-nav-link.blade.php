@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-3xl px-4 py-2.5 text-start text-sm font-semibold bg-primary text-primary-foreground transition'
            : 'block w-full rounded-3xl px-4 py-2.5 text-start text-sm font-medium text-muted-foreground hover:bg-card-alt hover:text-foreground transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
