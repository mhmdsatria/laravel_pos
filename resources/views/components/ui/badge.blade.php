@props(['status' => 'info', 'dot' => false])
<span {{ $attributes->merge(['class' => 'pos-badge pos-badge-' . $status]) }}>
    @if ($dot)<span class="pos-badge-dot" aria-hidden="true"></span>@endif
    {{ $slot }}
</span>
