@props(['items' => [], 'currentPath' => ''])
<aside id="desktopSidebar" class="pos-sidebar hidden md:flex" aria-label="Navigasi utama">
    <a href="{{ url('/dashboard') }}" class="pos-brand" aria-label="TB 39 POS">    
    <span>39</span><i aria-hidden="true"></i>
    <span class="pos-brand-text pos-nav-label hidden font-bold text-sm tracking-tight pl-2 text-primary dark:text-emerald-400">TB 39 SUKABUMI</span>
    </a>
    <nav class="pos-sidebar-nav">
        @foreach ($items as $nav)
            @php
                $isActive = request()->is($nav['pattern']) || ($nav['url'] === '/dashboard' && ($currentPath === 'dashboard' || $currentPath === '/'));
            @endphp
            <a href="{{ url($nav['url']) }}" class="pos-nav-item {{ $isActive ? 'is-active' : '' }}" aria-label="{{ $nav['label'] }}" title="{{ $nav['label'] }}">
                <span class="material-symbols-outlined">{{ $nav['icon'] }}</span>
                <span class="pos-nav-label hidden truncate">{{ $nav['label'] }}</span>
                <span class="pos-nav-tooltip">{{ $nav['label'] }}</span>
            </a>
        @endforeach
    </nav>
    <form method="POST" action="{{ route('logout') }}" class="pos-sidebar-footer">
        @csrf
        <button type="submit" class="pos-nav-item pos-nav-logout" aria-label="Keluar" title="Keluar">
            <span class="material-symbols-outlined">logout</span>
            <span class="pos-nav-label hidden font-bold text-xs text-rose-600">Keluar</span>
        </button>
    </form>
</aside>

<div id="mobileSidebar" class="pos-mobile-sidebar hidden md:hidden" aria-hidden="true">
    <button type="button" class="pos-mobile-backdrop" onclick="toggleMobileSidebar(false)" aria-label="Tutup navigasi"></button>
    <aside class="pos-mobile-panel">
        <div class="pos-mobile-heading">
            <a href="{{ url('/dashboard') }}" class="pos-mobile-brand"><span class="pos-brand-mini">39</span><span><strong>TB 39 POS</strong><small>Manajemen Toko Bangunan</small></span></a>
            <button type="button" class="pos-icon-btn" onclick="toggleMobileSidebar(false)" aria-label="Tutup"><span class="material-symbols-outlined">close</span></button>
        </div>
        <nav class="pos-mobile-nav">
            @foreach ($items as $nav)
                @php $isActive = request()->is($nav['pattern']); @endphp
                <a href="{{ url($nav['url']) }}" class="pos-mobile-link {{ $isActive ? 'is-active' : '' }}">
                    <span class="material-symbols-outlined">{{ $nav['icon'] }}</span><span>{{ $nav['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </aside>
</div>
