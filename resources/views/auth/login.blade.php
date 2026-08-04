<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Login | Toko Bangunan 39</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "on-primary-fixed-variant": "#005236",
                        "error": "#ba1a1a",
                        "outline": "#6c7a71",
                        "on-surface": "#131b2e",
                        "on-secondary-container": "#fefcff",
                        "secondary": "#0058be",
                        "surface-container": "#eaedff",
                        "outline-variant": "#bbcabf",
                        "surface-variant": "#dae2fd",
                        "on-primary-fixed": "#002113",
                        "on-tertiary": "#ffffff",
                        "on-tertiary-fixed-variant": "#842225",
                        "on-error-container": "#93000a",
                        "primary-fixed-dim": "#4edea3",
                        "on-secondary": "#ffffff",
                        "tertiary-container": "#fc7c78",
                        "on-primary": "#ffffff",
                        "secondary-fixed-dim": "#adc6ff",
                        "surface-container-low": "#f2f3ff",
                        "surface-container-highest": "#dae2fd",
                        "surface-tint": "#006c49",
                        "surface-bright": "#faf8ff",
                        "inverse-on-surface": "#eef0ff",
                        "error-container": "#ffdad6",
                        "inverse-surface": "#283044",
                        "secondary-container": "#2170e4",
                        "primary-container": "#10b981",
                        "on-error": "#ffffff",
                        "on-surface-variant": "#3c4a42",
                        "secondary-fixed": "#d8e2ff",
                        "on-secondary-fixed-variant": "#004395",
                        "tertiary": "#a43a3a",
                        "on-primary-container": "#00422b",
                        "primary": "#006c49",
                        "background": "#faf8ff",
                        "surface-container-lowest": "#ffffff",
                        "on-tertiary-container": "#711419",
                        "inverse-primary": "#4edea3",
                        "on-background": "#131b2e",
                        "surface-dim": "#d2d9f4",
                        "surface-container-high": "#e2e7ff",
                        "primary-fixed": "#6ffbbe",
                        "on-tertiary-fixed": "#410005",
                        "tertiary-fixed-dim": "#ffb3af",
                        "tertiary-fixed": "#ffdad7",
                        "surface": "#faf8ff",
                        "on-secondary-fixed": "#001a42"
                    },
                    borderRadius: {
                        "DEFAULT": "0.125rem",
                        "lg": "0.25rem",
                        "xl": "0.5rem",
                        "full": "0.75rem"
                    },
                    spacing: {
                        "lg": "24px",
                        "base": "4px",
                        "xl": "40px",
                        "margin-mobile": "16px",
                        "margin-desktop": "32px",
                        "xs": "4px",
                        "sm": "8px",
                        "gutter": "20px",
                        "md": "16px"
                    },
                    fontFamily: {
                        "headline-md": ["Inter"],
                        "headline-xl": ["Inter"],
                        "body-lg": ["Inter"],
                        "label-md": ["Inter"],
                        "headline-lg": ["Inter"],
                        "label-sm": ["Inter"],
                        "headline-lg-mobile": ["Inter"],
                        "body-md": ["Inter"]
                    },
                    fontSize: {
                        "headline-md": ["18px", {"lineHeight": "24px", "fontWeight": "600"}],
                        "headline-xl": ["36px", {"lineHeight": "44px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                        "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                        "label-md": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600"}],
                        "headline-lg": ["24px", {"lineHeight": "32px", "letterSpacing": "-0.01em", "fontWeight": "600"}],
                        "label-sm": ["11px", {"lineHeight": "14px", "fontWeight": "500"}],
                        "headline-lg-mobile": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                        "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}]
                    }
                }
            }
        };
    </script>
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-overlay {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        input:focus {
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
        }
    </style>
</head>
<body class="bg-surface-dim min-h-screen flex flex-col justify-between p-margin-mobile md:p-margin-desktop relative overflow-y-auto overflow-x-hidden">

    <div class="absolute inset-0 z-0 opacity-20 pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-primary-container rounded-full blur-[120px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-secondary-container rounded-full blur-[120px]"></div>
    </div>

    <div class="flex-1 flex items-center justify-center w-full z-10">
        <main class="w-full max-w-[400px]">
            <div class="bg-surface-container-lowest border border-outline-variant shadow-lg rounded-xl overflow-hidden animate-in fade-in zoom-in duration-500">
                
                <div class="p-lg text-center border-b border-outline-variant/30">
                    <div class="flex items-center justify-center mb-md">
                        <div class="w-11 h-11 bg-primary rounded-lg flex items-center justify-center text-on-primary">
                            <span class="material-symbols-outlined text-[28px]">construction</span>
                        </div>
                    </div>
                    <h1 class="font-headline-lg text-headline-lg text-primary tracking-tight">TB 39 Management</h1>
                    <p class="font-body-md text-body-md text-on-surface-variant mt-xs">Building Materials Sales Portal</p>
                </div>
                
                <form class="p-lg space-y-md" method="POST" action="/login">
                    @csrf

                    @if ($errors->any())
                        <div id="error-alert" class="relative bg-error/10 border border-error text-error rounded-lg p-md pr-lg flex items-start gap-sm transition-all duration-300">
                            <span class="material-symbols-outlined text-[22px] leading-none mt-[1px] shrink-0">warning</span>
                            <div class="text-left pr-2">
                                <p class="font-label-md text-label-md uppercase tracking-wide font-semibold">Login gagal</p>
                                <p class="font-body-md text-body-md mt-xs">{{ $errors->first() }}</p>
                            </div>
                            <button id="btn-close-alert" class="absolute top-2 right-2 text-error/60 hover:text-error transition-colors focus:outline-none" type="button" aria-label="Dismiss alert">
                                <span class="material-symbols-outlined text-[18px]">close</span>
                            </button>
                        </div>
                    @endif

                    <div class="space-y-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant block" for="username">USERNAME</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-outline text-[20px]">person</span>
                            <input class="w-full pl-xl pr-md py-md bg-surface-container-lowest border border-outline-variant rounded-lg font-body-md text-body-md focus:outline-none focus:border-primary-container transition-all" id="username" name="username" value="{{ old('username') }}" placeholder="Enter your credentials" required type="text" autocomplete="username" autofocus/>
                        </div>
                    </div>

                    <div class="space-y-xs">
                        <div class="flex justify-between items-center">
                            <label class="font-label-md text-label-md text-on-surface-variant block" for="password">PASSWORD</label>
                            <a class="font-label-md text-label-md text-secondary hover:text-secondary-container transition-colors" href="#" onclick="return false;">FORGOT PASSWORD?</a>
                        </div>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-outline text-[20px]">lock</span>
                            <input class="w-full pl-xl pr-[48px] py-md bg-surface-container-lowest border border-outline-variant rounded-lg font-body-md text-body-md focus:outline-none focus:border-primary-container transition-all" id="password" name="password" placeholder="••••••••" required type="password" autocomplete="current-password"/>
                            <button class="absolute right-md top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface transition-colors" type="button">
                                <span class="material-symbols-outlined text-[20px]">visibility</span>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center space-x-sm pt-xs">
                        <input class="w-4 h-4 text-primary border-outline-variant rounded focus:ring-primary-container" id="remember" name="remember" type="checkbox"/>
                        <label class="font-body-md text-body-md text-on-surface-variant cursor-pointer select-none" for="remember">Stay signed in for 30 days</label>
                    </div>

                    <button class="w-full py-md bg-primary hover:bg-on-primary-fixed-variant text-on-primary font-headline-md text-headline-md rounded-lg shadow-sm active:scale-[0.98] transition-all flex items-center justify-center space-x-sm mt-sm" type="submit">
                        <span>Login to Dashboard</span>
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </button>
                </form>

                <div class="bg-surface-container-low p-md border-t border-outline-variant/30 text-center">
                    <p class="font-label-md text-label-md text-on-surface-variant">Authorized Personnel Only. <br class="md:hidden"/> Access is logged and monitored.</p>
                </div>
            </div>
        </main>
    </div>

    <footer class="w-full text-center space-y-sm z-10 mt-12 pb-2 shrink-0">
        <div class="flex justify-center space-x-lg text-outline">
            <a class="font-label-md text-label-md hover:text-on-surface transition-colors" href="#" onclick="return false;">Security Policy</a>
            <a class="font-label-md text-label-md hover:text-on-surface transition-colors" href="#" onclick="return false;">System Status</a>
            <a class="font-label-md text-label-md hover:text-on-surface transition-colors" href="#" onclick="return false;">Contact Support</a>
        </div>
        <p class="font-label-sm text-label-sm text-outline uppercase tracking-widest">© 2024 Toko Bangunan 39 Systems. All Rights Reserved.</p>
    </footer>

    <div class="fixed bottom-0 left-0 p-margin-desktop opacity-30 select-none pointer-events-none hidden lg:block">
        <div class="flex items-center space-x-sm border-l-4 border-primary pl-md py-sm">
            <span class="font-headline-xl text-headline-xl text-on-surface-variant/20 font-black">STRENGTH</span>
            <span class="font-headline-xl text-headline-xl text-on-surface-variant/20 font-black">/</span>
            <span class="font-headline-xl text-headline-xl text-on-surface-variant/20 font-black">PRECISION</span>
        </div>
    </div>

    <script>
        const toggleBtn = document.querySelector('form button[type="button"]');
        const passInput = document.querySelector('#password');
        const icon = toggleBtn.querySelector('.material-symbols-outlined');
        
        // Password Visibility Toggle
        toggleBtn.addEventListener('click', () => {
            const isPass = passInput.type === 'password';
            passInput.type = isPass ? 'text' : 'password';
            icon.textContent = isPass ? 'visibility_off' : 'visibility';
        });

        // Close Alert Functionality
        const btnCloseAlert = document.querySelector('#btn-close-alert');
        const errorAlert = document.querySelector('#error-alert');
        if (btnCloseAlert && errorAlert) {
            btnCloseAlert.addEventListener('click', () => {
                errorAlert.style.display = 'none';
            });
        }

        // Dynamic Icon Color on Input Focus (Spesifik hanya untuk input teks & password)
        const inputs = document.querySelectorAll('input[type="text"], input[type="password"]');
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                const iconEl = input.parentElement.querySelector('.material-symbols-outlined');
                if(iconEl) iconEl.style.color = '#10b981';
            });
            input.addEventListener('blur', () => {
                const iconEl = input.parentElement.querySelector('.material-symbols-outlined');
                if(iconEl) iconEl.style.color = '';
            });
        });
    </script>
</body>
</html>