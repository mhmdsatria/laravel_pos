@props(['caption' => null])
<div {{ $attributes->merge(['class' => 'pos-table-shell']) }}>
    <table class="pos-data-table">
        @if ($caption)<caption class="sr-only">{{ $caption }}</caption>@endif
        {{ $slot }}
    </table>
</div>
