@props(['id', 'title' => null, 'size' => 'md'])
<div id="{{ $id }}" {{ $attributes->merge(['class' => 'pos-modal hidden']) }} role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
    <div class="pos-modal-backdrop" data-modal-close="{{ $id }}"></div>
    <section class="pos-modal-panel pos-modal-{{ $size }}">
        @if ($title || isset($header))
            <header class="pos-modal-header">
                <div>
                    @if ($title)<h2 id="{{ $id }}-title">{{ $title }}</h2>@endif
                    @isset($header){{ $header }}@endisset
                </div>
            </header>
        @endif
        <div class="pos-modal-body">{{ $slot }}</div>
        @isset($footer)<footer class="pos-modal-footer">{{ $footer }}</footer>@endisset
    </section>
</div>
