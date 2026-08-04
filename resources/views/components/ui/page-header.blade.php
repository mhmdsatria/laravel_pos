@props(['title', 'subtitle' => null])
<header {{ $attributes->merge(['class' => 'pos-page-header']) }}>
    <div class="pos-page-heading">
        <div class="pos-page-title-row">
            <h1>{{ $title }}</h1>
            @isset($meta)<div class="pos-page-meta">{{ $meta }}</div>@endisset
        </div>
        @if ($subtitle)<p>{{ $subtitle }}</p>@endif
    </div>
    @isset($actions)<div class="pos-page-actions">{{ $actions }}</div>@endisset
</header>
