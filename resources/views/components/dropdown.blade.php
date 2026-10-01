@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1'])

@php
$alignmentClasses = match ($align) {
    'left' => 'start-0',
    'top' => 'start-0',
    default => 'end-0',
};

$width = match ($width) {
    '48' => '',
    default => $width,
};
@endphp

<div class="dropdown position-relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <div @click="open = ! open">
        {{ $trigger }}
    </div>

    <div x-show="open" :class="{ 'show': open }"
            class="dropdown-menu mt-2 {{ $width }} {{ $alignmentClasses }}"
            style="display: none;"
            @click="open = false">
        <div class="{{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>
