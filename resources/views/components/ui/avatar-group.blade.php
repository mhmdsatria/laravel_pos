@props(['items' => [], 'limit' => 4])
<div {{ $attributes->merge(['class' => 'pos-avatar-group']) }}>
    @foreach (collect($items)->take($limit) as $item)
        @php
            $name = is_array($item) ? ($item['name'] ?? 'Pengguna') : (string) $item;
            $initial = strtoupper(substr($name, 0, 1));
        @endphp
        <span class="pos-avatar" title="{{ $name }}">{{ $initial }}</span>
    @endforeach
    @if (count($items) > $limit)<span class="pos-avatar pos-avatar-more">+{{ count($items) - $limit }}</span>@endif
</div>
