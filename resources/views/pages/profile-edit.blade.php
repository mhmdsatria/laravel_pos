@extends('layouts.app')

@section('title', 'Edit Profil | Toko Bangunan 39')
@section('page_title', 'Edit Profil')

@section('content')
<div class="space-y-lg">
    {{-- Page Title & Navigation Header --}}
    <div
        class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between border-b border-outline-variant/60 pb-md">
        <div>
            <h2 class="font-headline-xl text-headline-xl uppercase text-primary font-bold tracking-tight">Edit Profil
                User</h2>
            <p class="mt-0.5 text-xs text-on-surface-variant">Ubah nama lengkap, username login, dan pengaturan
                kredensial password akun aktif Anda.</p>
        </div>

        <!-- <a href="{{ route('dashboard') }}"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold border border-outline-variant rounded-xl text-on-surface-variant hover:bg-surface-container-high transition-all active:scale-95 w-fit shadow-sm bg-white">
            <span class="material-symbols-outlined text-lg">arrow_back</span>
            <span>Kembali ke Dashboard</span>
        </a> -->
    </div>

    {{-- Main Layout Grid --}}
    <div class="grid grid-cols-1 gap-lg xl:grid-cols-3 items-start">

        {{-- Left Column: Active Account Summary --}}
        <section class="bg-white border border-outline-variant rounded-xl overflow-hidden shadow-sm xl:col-span-1">
            <div class="px-lg py-4 border-b border-outline-variant/60 bg-surface-container-low/40">
                <h3 class="text-sm font-bold text-on-surface uppercase tracking-wider">Akun Aktif</h3>
                <p class="text-[11px] text-on-surface-variant mt-0.5">Informasi sesi pengguna yang sedang masuk ke
                    sistem.</p>
            </div>

            <div class="p-lg space-y-lg">
                {{-- User Branding Badge --}}
                <div
                    class="flex items-center gap-md rounded-xl border border-outline-variant bg-surface-container-low/40 p-md">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary shadow-sm">
                        <span class="material-symbols-outlined text-3xl">account_circle</span>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-on-surface leading-tight">{{ $user->nama_lengkap }}
                        </p>
                        <p class="truncate font-mono text-xs text-primary mt-0.5">{{ '@' . $user->username }}</p>
                    </div>
                </div>

                {{-- Metadata List --}}
                <div class="space-y-1 text-xs">
                    <div class="flex justify-between gap-md border-b border-outline-variant/40 py-2.5">
                        <span class="text-on-surface-variant font-medium">ID Pengguna</span>
                        <strong class="text-on-surface font-mono">#{{ $user->id }}</strong>
                    </div>
                    <div class="flex justify-between gap-md border-b border-outline-variant/40 py-2.5">
                        <span class="text-on-surface-variant font-medium">Waktu Registrasi</span>
                        <strong
                            class="text-on-surface">{{ optional($user->created_at)->format('d M Y H:i') ?? '-' }}</strong>
                    </div>
                    <div class="flex justify-between gap-md py-2.5">
                        <span class="text-on-surface-variant font-medium">Pembaruan Terakhir</span>
                        <strong
                            class="text-on-surface">{{ optional($user->updated_at)->format('d M Y H:i') ?? '-' }}</strong>
                    </div>
                </div>
            </div>
        </section>

        {{-- Right Column: Profile Form fields --}}
        <section class="bg-white border border-outline-variant rounded-xl overflow-hidden shadow-sm xl:col-span-2">
            <div class="px-lg py-4 border-b border-outline-variant/60 bg-surface-container-lowest">
                <h3 class="text-sm font-bold text-on-surface uppercase tracking-wider">Form Perubahan Profil</h3>
                <p class="text-[11px] text-on-surface-variant mt-0.5">Perbarui data primer Anda di bawah ini secara
                    berkala untuk menjaga validitas dokumen kasir.</p>
            </div>

            <form method="POST" action="{{ route('profile.update') }}"
                class="space-y-lg p-lg bg-surface-container-lowest">
                @csrf
                @method('PUT')

                {{-- Primary Data Inputs --}}
                <div class="grid grid-cols-1 gap-md md:grid-cols-2">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide"
                            for="nama_lengkap">Nama Lengkap <span class="text-error font-normal">*</span></label>
                        <input id="nama_lengkap" name="nama_lengkap" type="text"
                            value="{{ old('nama_lengkap', $user->nama_lengkap) }}"
                            class="w-full bg-surface border border-outline-variant rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all font-semibold"
                            autocomplete="name" required>
                        @error('nama_lengkap')
                        <p class="mt-1 text-xs font-semibold text-error flex items-center gap-1"><span
                                class="material-symbols-outlined text-sm">info</span>{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wide"
                            for="username">Username Akses</label>
                        <input id="username" name="username" type="text" value="{{ $user->username }}"
                            class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl px-4 py-2 text-sm font-mono text-on-surface-variant/70 cursor-not-allowed outline-none"
                            readonly {{-- FIX: Mengunci input agar tidak bisa diedit --}}>
                    </div>
                </div>

                {{-- Security Section: Password Reset Container --}}
                <div class="rounded-xl border border-outline-variant/80 bg-surface-container-low/50 p-4 space-y-4">
                    <div class="flex items-start gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center shrink-0 shadow-sm border border-amber-500/10">
                            <span class="material-symbols-outlined text-xl">lock_reset</span>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-on-surface uppercase tracking-wider leading-none">Opsi Ubah
                                Kata Sandi Akun</p>
                            <p class="text-[11px] text-on-surface-variant mt-1">Kosongkan seluruh kolom di bawah ini
                                jika Anda tidak ingin memperbarui password login saat ini.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-md md:grid-cols-3 pt-2">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-on-surface-variant"
                                for="current_password">Password Lama</label>
                            <input id="current_password" name="current_password" type="password"
                                class="w-full bg-white border border-outline-variant rounded-xl px-3 py-1.5 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all"
                                autocomplete="current-password" placeholder="Masukkan sandi saat ini">
                            @error('current_password')
                            <p class="mt-1 text-[11px] font-semibold text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-on-surface-variant" for="password">Password
                                Baru</label>
                            <input id="password" name="password" type="password"
                                class="w-full bg-white border border-outline-variant rounded-xl px-3 py-1.5 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all"
                                autocomplete="new-password" placeholder="Minimal 6 karakter baru">
                            @error('password')
                            <p class="mt-1 text-[11px] font-semibold text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-on-surface-variant"
                                for="password_confirmation">Konfirmasi Password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password"
                                class="w-full bg-white border border-outline-variant rounded-xl px-3 py-1.5 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all"
                                autocomplete="new-password" placeholder="Ulangi sandi baru">
                        </div>
                    </div>
                </div>

                {{-- Form Actions Buttons Block --}}
                <div
                    class="flex flex-col-reverse gap-2 border-t border-outline-variant/60 pt-md sm:flex-row sm:justify-end">
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex h-10 items-center justify-center rounded-xl border border-outline-variant bg-white px-5 text-xs font-bold text-on-surface-variant hover:bg-surface-container-high transition-all active:scale-95 shadow-sm cursor-pointer">
                        Batal
                    </a>
                    <button type="submit"
                        class="inline-flex h-10 items-center justify-center gap-1.5 bg-primary text-on-primary px-5 rounded-xl text-xs font-bold shadow-md hover:brightness-110 transition-all active:scale-95 cursor-pointer">
                        <span class="material-symbols-outlined text-base">save</span>
                        <span>Simpan Pembaruan Profil</span>
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection