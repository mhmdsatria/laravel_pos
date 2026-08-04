@props(['title' => null, 'subtitle' => null])
<section {{ $attributes->merge(['class' => 'pos-card pos-chart-card']) }}>
    @if ($title || $subtitle || isset($actions))
        <header class="pos-card-heading">
            <div>
                @if ($title)<h3 class="pos-card-title">{{ $title }}</h3>@endif
                @if ($subtitle)<p class="pos-card-subtitle">{{ $subtitle }}</p>@endif
            </div>
            @isset($actions)<div class="pos-card-actions">{{ $actions }}</div>@endisset
        </header>
    @endif
    <div class="pos-chart-card-body">{{ $slot }}</div>
</section>
