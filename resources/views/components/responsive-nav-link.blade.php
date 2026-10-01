@props(['active'])

@php
$classes = ($active ?? false)
            ? 'nav-link active d-block w-100'
            : 'nav-link d-block w-100';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
