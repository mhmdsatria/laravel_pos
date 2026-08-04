@props([
    'variant' => 'primary',
    'type' => 'button',
    'href' => null,
    'icon' => null,
])
@php
    $classes = 'pos-btn pos-btn-' . $variant;
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<span class="material-symbols-outlined pos-btn-icon">{{ $icon }}</span>@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<span class="material-symbols-outlined pos-btn-icon">{{ $icon }}</span>@endif
        {{ $slot }}
    </button>
@endif
