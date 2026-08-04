@props(['title' => 'Belum ada data', 'description' => null, 'icon' => 'inbox'])
<div {{ $attributes->merge(['class' => 'pos-empty-state']) }}>
    <span class="material-symbols-outlined pos-empty-icon">{{ $icon }}</span>
    <strong>{{ $title }}</strong>
    @if ($description)<p>{{ $description }}</p>@endif
    @isset($action)<div class="pos-empty-action">{{ $action }}</div>@endisset
</div>
