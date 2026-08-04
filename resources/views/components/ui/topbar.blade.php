@props(['userName' => 'Admin Kasir', 'initial' => 'A'])
<header class="pos-topbar">
    <div class="pos-topbar-search-wrap">
        <button class="pos-icon-btn" id="appSidebarToggle" onclick="handleSidebarToggle()" type="button" aria-label="Buka/Tutup Navigasi" title="Buka/Tutup Navigasi">
            <span class="material-symbols-outlined" id="sidebarToggleIcon">menu</span>
        </button>
        <form action="" method="GET" id="globalSearchForm" class="pos-global-search" onsubmit="handleGlobalSearchSubmit(event)">
            <span class="material-symbols-outlined">search</span>
            <input type="search" id="globalSearchInput" name="search" value="{{ request('search') ?? request('q') ?? '' }}" placeholder="Cari barang, transaksi, pelanggan, atau invoice..." aria-label="Pencarian global" autocomplete="off">
        </form>
    </div>
    <a href="{{ url('/sales') }}" class="pos-btn pos-btn-primary pos-topbar-new"><span class="material-symbols-outlined">add</span><span class="hidden sm:inline">Transaksi Baru</span></a>
    <div class="pos-topbar-actions">
        <button type="button" onclick="toggleThemeMode()" class="pos-icon-btn" title="Ganti tema" aria-label="Ganti tema terang atau gelap">
            <span class="material-symbols-outlined pos-theme-light">light_mode</span>
            <span class="material-symbols-outlined pos-theme-dark">dark_mode</span>
        </button>
        
    </div>
    <div class="pos-profile" id="userProfileDropdown">
            <button type="button" onclick="toggleUserDropdown()" class="pos-profile-trigger" aria-haspopup="true" aria-expanded="false">
                <span class="pos-avatar pos-avatar-profile">{{ $initial }}</span>
                <span class="material-symbols-outlined pos-profile-arrow" id="dropdownArrow">expand_more</span>
            </button>
            <div class="pos-profile-menu hidden" id="dropdownMenu">
                <div class="pos-profile-summary"><strong>{{ $userName }}</strong><span><i></i>Aktif</span></div>
                <a href="{{ route('profile.edit') }}"><span class="material-symbols-outlined">account_circle</span>Ubah Profil</a>
                <a href="{{ route('settings.index') }}"><span class="material-symbols-outlined">settings</span>Pengaturan</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit"><span class="material-symbols-outlined">logout</span>Keluar</button></form>
            </div>
        </div>
        
</header>
