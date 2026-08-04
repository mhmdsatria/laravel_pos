<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Toko Bangunan 39')</title>
    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])
    @stack('head')
<style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .sidebar-menu-group > summary::-webkit-details-marker { display: none; }
        .sidebar-menu-group[open] > summary .group-chevron { transform: rotate(180deg); }
        body.modal-open { overflow: hidden; }

        #desktopSidebar {
            transition:
                width 220ms ease,
                min-width 220ms ease,
                opacity 180ms ease,
                transform 220ms ease,
                border-color 180ms ease;
        }

        #desktopSidebar.desktop-sidebar-collapsed {
            width: 0 !important;
            min-width: 0 !important;
            opacity: 0;
            transform: translateX(-100%);
            border-color: transparent;
            overflow: hidden;
            pointer-events: none;
        }

        #desktopSidebarToggle {
            transition:
                background-color 180ms ease,
                color 180ms ease,
                transform 180ms ease;
        }

        #desktopSidebarToggle:active {
            transform: scale(0.94);
        }

        @media (max-width: 767px) {
            #desktopSidebar {
                display: none !important;
            }
        }

        .zenith-card {
            background: #ffffff;
            border: 1px solid #eef0ff;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border-radius: 1rem;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-surface text-on-surface font-body-md overflow-hidden h-screen flex">
    @php
        $activeUserName = auth()->user()->nama_lengkap ?? auth()->user()->username ?? 'Admin Kasir';
        $activeInitial = strtoupper(substr($activeUserName, 0, 1));

        $dashboardItem = [
            'label' => 'Dashboard',
            'icon' => 'dashboard',
            'url' => '/dashboard',
            'patterns' => ['dashboard'],
        ];

        $menuGroups = [
            [
                'label' => 'Master Data',
                'icon' => 'database',
                'items' => [
                    ['label' => 'Master Pelanggan', 'icon' => 'group', 'url' => '/customer', 'patterns' => ['customer*']],
                    ['label' => 'Master Barang', 'icon' => 'inventory_2', 'url' => '/product', 'patterns' => ['product*']],
                    ['label' => 'Supplier & Sales', 'icon' => 'settings_suggest', 'url' => '/master-management', 'patterns' => ['master-management*']],
                    ['label' => 'Master Harga', 'icon' => 'payments', 'url' => '/pricing', 'patterns' => ['pricing*']],
                ],
            ],
            [
                'label' => 'Transaksi',
                'icon' => 'swap_horiz',
                'items' => [
                    ['label' => 'Transaksi Penjualan', 'icon' => 'receipt_long', 'url' => '/sales', 'patterns' => ['sales*']],
                    ['label' => 'Pembelian / Restock', 'icon' => 'shopping_cart', 'url' => '/purchase', 'patterns' => ['purchase*']],
                    ['label' => 'Piutang', 'icon' => 'account_balance_wallet', 'url' => '/receivables', 'patterns' => ['receivables*']],
                ],
            ],
            [
                'label' => 'Persediaan',
                'icon' => 'inventory',
                'items' => [
                    ['label' => 'Stock Adjustment', 'icon' => 'rule', 'url' => '/adjustment', 'patterns' => ['adjustment*']],
                    ['label' => 'Laporan Stok', 'icon' => 'fact_check', 'url' => '/inventory-report', 'patterns' => ['inventory-report*']],
                ],
            ],
            [
                'label' => 'Laporan',
                'icon' => 'analytics',
                'items' => [
                    ['label' => 'Laporan Keuangan', 'icon' => 'monitoring', 'url' => '/reports', 'patterns' => ['reports*']],
                ],
            ],
        ];

        $isActiveMenu = function (array $item): bool {
            foreach ($item['patterns'] as $pattern) {
                if (request()->is($pattern)) {
                    return true;
                }
            }

            return false;
        };

        $isActiveGroup = function (array $group) use ($isActiveMenu): bool {
            foreach ($group['items'] as $item) {
                if ($isActiveMenu($item)) {
                    return true;
                }
            }

            return false;
        };
    @endphp

    <aside id="desktopSidebar" class="hidden md:flex flex-col h-full w-64 bg-surface-container-lowest border-r border-outline-variant py-lg shrink-0">
        <div class="px-lg mb-lg">
            <div class="flex items-center gap-md">
                <div class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center text-on-primary">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">architecture</span>
                </div>
                <div class="min-w-0">
                    <h1 class="font-headline-md text-headline-md font-bold text-primary leading-tight truncate">TB 39 Management</h1>
                    <p class="font-label-md text-[10px] text-on-surface-variant uppercase tracking-widest truncate">Building Materials</p>
                </div>
            </div>
        </div>

        <nav class="flex-1 space-y-2 overflow-y-auto hide-scrollbar px-2">
            @php $dashboardActive = $isActiveMenu($dashboardItem); @endphp

            <a
                class="flex items-center gap-md px-md py-sm {{ $dashboardActive ? 'bg-primary text-on-primary shadow-md' : 'text-on-surface-variant hover:bg-surface-container-high' }} rounded-xl transition-all"
                href="{{ url($dashboardItem['url']) }}"
            >
                <span class="material-symbols-outlined">{{ $dashboardItem['icon'] }}</span>
                <span class="font-label-md text-label-md truncate">{{ $dashboardItem['label'] }}</span>
            </a>

            @foreach ($menuGroups as $group)
                @php $groupActive = $isActiveGroup($group); @endphp

                <details class="sidebar-menu-group" @if($groupActive) open @endif>
                    <summary
                        class="flex cursor-pointer list-none items-center justify-between rounded-xl px-md py-sm transition-colors {{ $groupActive ? 'bg-primary/10 text-primary' : 'text-on-surface-variant hover:bg-surface-container-high' }}"
                    >
                        <span class="flex min-w-0 items-center gap-md">
                            <span class="material-symbols-outlined">{{ $group['icon'] }}</span>
                            <span class="truncate font-label-md text-label-md">{{ $group['label'] }}</span>
                        </span>

                        <span class="material-symbols-outlined text-[18px] transition-transform group-chevron">
                            expand_more
                        </span>
                    </summary>

                    <div class="mt-1 space-y-1 pl-md">
                        @foreach ($group['items'] as $item)
                            @php $active = $isActiveMenu($item); @endphp

                            <a
                                class="flex items-center gap-sm rounded-xl px-md py-sm transition-all {{ $active ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-high' }}"
                                href="{{ url($item['url']) }}"
                            >
                                <span class="material-symbols-outlined text-[19px]">{{ $item['icon'] }}</span>
                                <span class="truncate font-label-md text-label-md">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </details>
            @endforeach
        </nav>

        <!-- <div class="px-md mt-auto space-y-4 pt-md border-t border-outline-variant">
            <a class="w-full bg-primary text-on-primary font-bold py-sm rounded-xl flex items-center justify-center gap-xs hover:opacity-90 transition-opacity shadow-lg shadow-primary/20" href="{{ url('/sales') }}">
                <span class="material-symbols-outlined">add</span>
                <span class="font-label-md text-label-md">Transaksi Baru</span>
            </a>
            <div class="space-y-1">
                <a class="flex items-center gap-md px-md py-sm text-on-surface-variant hover:bg-surface-container-high transition-all rounded-xl cursor-pointer" href="{{ route('settings.index') }}">
                    <span class="material-symbols-outlined">settings</span>
                    <span class="font-label-md text-label-md">Settings</span>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="mx-0">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-md px-md py-sm text-error hover:bg-error-container transition-all rounded-xl cursor-pointer text-left">
                        <span class="material-symbols-outlined">logout</span>
                        <span class="font-label-md text-label-md">Logout</span>
                    </button>
                </form>
            </div>
        </div> -->
    </aside>

    <div id="mobileSidebar" class="fixed inset-0 z-[80] hidden md:hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleMobileSidebar(false)"></div>
        <aside class="relative flex flex-col h-full w-72 bg-surface-container-lowest border-r border-outline-variant py-lg shadow-2xl">
            <div class="px-lg mb-lg flex items-center justify-between">
                <div class="flex items-center gap-md">
                    <div class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center text-on-primary">
                        <span class="material-symbols-outlined">architecture</span>
                    </div>
                    <div>
                        <h1 class="font-headline-md text-headline-md font-bold text-primary leading-tight">TB 39</h1>
                        <p class="font-label-md text-[10px] text-on-surface-variant uppercase tracking-widest">Management</p>
                    </div>
                </div>
                <button class="p-2 rounded-full hover:bg-surface-container-high" onclick="toggleMobileSidebar(false)" type="button">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <nav class="flex-1 space-y-2 overflow-y-auto hide-scrollbar px-2">
                @php $dashboardActive = $isActiveMenu($dashboardItem); @endphp

                <a
                    class="flex items-center gap-md px-md py-sm {{ $dashboardActive ? 'bg-primary text-on-primary shadow-md' : 'text-on-surface-variant hover:bg-surface-container-high' }} rounded-xl"
                    href="{{ url($dashboardItem['url']) }}"
                >
                    <span class="material-symbols-outlined">{{ $dashboardItem['icon'] }}</span>
                    <span class="font-label-md text-label-md truncate">{{ $dashboardItem['label'] }}</span>
                </a>

                @foreach ($menuGroups as $group)
                    @php $groupActive = $isActiveGroup($group); @endphp

                    <details class="sidebar-menu-group" @if($groupActive) open @endif>
                        <summary
                            class="flex cursor-pointer list-none items-center justify-between rounded-xl px-md py-sm {{ $groupActive ? 'bg-primary/10 text-primary' : 'text-on-surface-variant hover:bg-surface-container-high' }}"
                        >
                            <span class="flex min-w-0 items-center gap-md">
                                <span class="material-symbols-outlined">{{ $group['icon'] }}</span>
                                <span class="truncate font-label-md text-label-md">{{ $group['label'] }}</span>
                            </span>

                            <span class="material-symbols-outlined text-[18px] transition-transform group-chevron">
                                expand_more
                            </span>
                        </summary>

                        <div class="mt-1 space-y-1 pl-md">
                            @foreach ($group['items'] as $item)
                                @php $active = $isActiveMenu($item); @endphp

                                <a
                                    class="flex items-center gap-sm rounded-xl px-md py-sm {{ $active ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-high' }}"
                                    href="{{ url($item['url']) }}"
                                >
                                    <span class="material-symbols-outlined text-[19px]">{{ $item['icon'] }}</span>
                                    <span class="truncate font-label-md text-label-md">{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </details>
                @endforeach
            </nav>
        </aside>
    </div>

    <main class="flex-1 flex flex-col min-w-0 bg-background overflow-hidden relative">
        <header class="flex justify-between items-center w-full px-margin-mobile md:px-margin-desktop h-16 bg-surface border-b border-outline-variant shrink-0">
            <div class="flex items-center gap-4 min-w-0">
                <button
                    id="desktopSidebarToggle"
                    class="hidden md:inline-flex w-10 h-10 items-center justify-center rounded-xl text-on-surface-variant hover:bg-surface-container-high hover:text-primary"
                    type="button"
                    onclick="toggleDesktopSidebar()"
                    aria-controls="desktopSidebar"
                    aria-expanded="true"
                    title="Sembunyikan sidebar"
                >
                    <span class="material-symbols-outlined" id="desktopSidebarToggleIcon">menu_open</span>
                </button>

                <button class="md:hidden p-2 hover:bg-surface-container-low rounded-full transition-colors" onclick="toggleMobileSidebar(true)" type="button">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <div class="min-w-0">
                    <h2 class="font-headline-lg-mobile md:font-headline-lg text-primary font-black truncate">@yield('page_title', 'Dashboard')</h2>
                    <p class="hidden sm:block text-[11px] text-on-surface-variant uppercase tracking-widest">Toko Bangunan 39</p>
                </div>
            </div>
            <div class="flex items-center gap-md">
                <!-- <button class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-surface-container-low transition-colors text-on-surface-variant" type="button">
                    <span class="material-symbols-outlined">notifications</span>
                </button> -->
                <div class="h-8 w-[1px] bg-outline-variant hidden sm:block"></div>
                <div class="relative inline-block text-left" id="userProfileDropdown">
    
    <div onclick="toggleUserDropdown()" class="flex items-center gap-sm cursor-pointer hover:bg-surface-container-low p-1.5 rounded-full transition-colors select-none">
        <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center text-primary font-bold text-xs">
            {{ $activeInitial }}
        </div>
        <div class="hidden lg:block text-left pr-xs">
            <p class="font-label-md text-[13px] leading-none mb-1 text-on-surface">{{ $activeUserName }}</p>
            <p class="text-[10px] text-primary uppercase font-bold flex items-center gap-1">
                <span class="w-1.5 h-1.5 bg-green-500 rounded-full inline-block animate-pulse"></span>
                Online
            </p>
        </div>
        <span class="material-symbols-outlined text-[16px] text-outline transition-transform duration-200" id="dropdownArrow">expand_more</span>
    </div>

    <div class="absolute right-0 mt-2 w-52 bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xl p-xs hidden opacity-0 translate-y-2 transition-all duration-200 z-50 origin-top-right" id="dropdownMenu">
        <div class="space-y-1">
                        <a class="flex items-center gap-md px-md py-sm text-on-surface-variant hover:bg-surface-container-high transition-all rounded-xl cursor-pointer" href="{{ route('profile.edit') }}">
                <span class="material-symbols-outlined text-[20px]">account_circle</span>
                <span class="font-label-md text-label-md">Edit Profil</span>
            </a>
            
<a class="flex items-center gap-md px-md py-sm text-on-surface-variant hover:bg-surface-container-high transition-all rounded-xl cursor-pointer" href="{{ route('settings.index') }}">
                <span class="material-symbols-outlined text-[20px]">settings</span>
                <span class="font-label-md text-label-md">Settings</span>
            </a>
            
            <div class="border-t border-outline-variant my-xs"></div>

            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="w-full flex items-center gap-md px-md py-sm text-error hover:bg-error-container/40 transition-all rounded-xl cursor-pointer text-left">
                    <span class="material-symbols-outlined text-[20px]">logout</span>
                    <span class="font-label-md text-label-md">Logout</span>
                </button>
            </form>
        </div>
    </div>
</div>
            </div>
        </header>

        <section class="flex-1 overflow-y-auto p-margin-mobile md:p-margin-desktop space-y-lg hide-scrollbar">
            @if (session('success'))
                <div class="bg-primary/10 border border-primary text-primary px-4 py-3 rounded-xl flex items-center gap-2 animate-pulse">
                    <span class="material-symbols-outlined">check_circle</span>
                    <span class="font-body-md text-body-md">{{ session('success') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-error/10 border border-error text-error px-4 py-3 rounded-xl flex items-start gap-2 animate-pulse">
                    <span class="material-symbols-outlined">warning</span>
                    <div class="font-body-md text-body-md">
                        {{ $errors->first() }}
                    </div>
                </div>
            @endif

            @yield('content')
        </section>
    </main>

    <script>
        const DESKTOP_SIDEBAR_STORAGE_KEY = 'tb39.desktopSidebarCollapsed';

        function setDesktopSidebarState(collapsed, persist = true) {
            const sidebar = document.getElementById('desktopSidebar');
            const toggleButton = document.getElementById('desktopSidebarToggle');
            const toggleIcon = document.getElementById('desktopSidebarToggleIcon');

            if (!sidebar || !toggleButton || !toggleIcon) {
                return;
            }

            sidebar.classList.toggle('desktop-sidebar-collapsed', collapsed);
            toggleIcon.textContent = collapsed ? 'menu' : 'menu_open';
            toggleButton.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            toggleButton.setAttribute(
                'title',
                collapsed ? 'Tampilkan sidebar' : 'Sembunyikan sidebar'
            );

            if (persist) {
                try {
                    window.localStorage.setItem(
                        DESKTOP_SIDEBAR_STORAGE_KEY,
                        collapsed ? '1' : '0'
                    );
                } catch (error) {
                    console.warn('Status sidebar tidak dapat disimpan.', error);
                }
            }
        }

        function toggleDesktopSidebar() {
            const sidebar = document.getElementById('desktopSidebar');

            if (!sidebar) {
                return;
            }

            const willCollapse = !sidebar.classList.contains(
                'desktop-sidebar-collapsed'
            );

            setDesktopSidebarState(willCollapse);
        }

        function restoreDesktopSidebarState() {
            let collapsed = false;

            try {
                collapsed =
                    window.localStorage.getItem(
                        DESKTOP_SIDEBAR_STORAGE_KEY
                    ) === '1';
            } catch (error) {
                console.warn('Status sidebar tidak dapat dibaca.', error);
            }

            setDesktopSidebarState(collapsed, false);
        }

        function toggleMobileSidebar(show) {
            const sidebar = document.getElementById('mobileSidebar');
            if (!sidebar) return;
            if (show) {
                sidebar.classList.remove('hidden');
                sidebar.classList.add('block');
            } else {
                sidebar.classList.add('hidden');
                sidebar.classList.remove('block');
            }
        }
        function toggleUserDropdown() {
        const menu = document.getElementById('dropdownMenu');
        const arrow = document.getElementById('dropdownArrow');
        
        if (menu.classList.contains('hidden')) {
            // Buka Dropdown
            menu.classList.remove('hidden');
            // Sedikit delay agar animasi transisi Tailwind berjalan lancar
            setTimeout(() => {
                menu.classList.remove('opacity-0', 'translate-y-2');
            }, 10);
            if (arrow) arrow.classList.add('rotate-180');
        } else {
            // Tutup Dropdown
            closeUserDropdown();
        }
    }

    function closeUserDropdown() {
        const menu = document.getElementById('dropdownMenu');
        const arrow = document.getElementById('dropdownArrow');
        
        if (menu && !menu.classList.contains('hidden')) {
            menu.classList.add('opacity-0', 'translate-y-2');
            if (arrow) arrow.classList.remove('rotate-180');
            
            // Tunggu transisi selesai sebelum menambahkan class hidden kembali
            setTimeout(() => {
                menu.classList.add('hidden');
            }, 200);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        restoreDesktopSidebarState();
    });

    // Menutup dropdown secara otomatis jika pengguna mengklik area luar menu
    document.addEventListener('click', function(event) {
        const dropdownContainer = document.getElementById('userProfileDropdown');
        if (dropdownContainer && !dropdownContainer.contains(event.target)) {
            closeUserDropdown();
        }
    });
    </script>
    @stack('modals')
    @stack('scripts')
</body>
</html>
