@props(['value'])

<label {{ $attributes->merge(['class' => 'mb-2 block text-sm font-medium text-foreground']) }}>
    {{ $value ?? $slot }}
</label>
