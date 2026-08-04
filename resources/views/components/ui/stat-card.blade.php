@props([
    'variant' => 'default',
    'label',
    'value',
    'change' => null,
    'changeStatus' => 'success',
    'description' => null,
    'icon' => null,
])
<article {{ $attributes->merge(['class' => 'pos-stat-card pos-stat-card-' . $variant]) }}>
    <div class="pos-stat-card-top">
        <span class="pos-stat-label">{{ $label }}</span>
        @if ($icon)<span class="material-symbols-outlined pos-stat-icon">{{ $icon }}</span>@endif
    </div>
    <div class="pos-stat-value-row">
        <strong class="pos-stat-value">{{ $value }}</strong>
        @if ($change)<x-ui.badge :status="$changeStatus">{{ $change }}</x-ui.badge>@endif
    </div>
    @if ($description)<p class="pos-stat-description">{{ $description }}</p>@endif
</article>
