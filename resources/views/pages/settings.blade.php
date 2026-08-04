@extends('layouts.app')

@section('title', 'Pengaturan Sistem | Toko Bangunan 39')
<!-- @section('page_title', 'Pengaturan Sistem') -->
<!-- @section('active_page', 'settings') -->

@section('content')
<div class="space-y-lg">
    <div>
        <h2 class="font-headline-xl text-headline-xl uppercase text-primary">Pengaturan Sistem & Cadangan Data</h2>
        <p class="mt-1 text-body-md text-on-surface-variant">Atur printer, folder lokal, backup, dan restore aplikasi desktop luring.</p>
    </div>

    <div class="grid grid-cols-1 gap-lg xl:grid-cols-2">
        <section class="tb-card">
            <div class="tb-card-header">
                <div>
                    <h3 class="tb-card-title">Printer Lokal</h3>
                    <p class="tb-card-subtitle">Printer terdeteksi dari sistem operasi atau NativePHP.</p>
                </div>
                <span class="tb-status {{ $nativeAvailable ? 'tb-status-success' : 'tb-status-warning' }}">
                    {{ $nativeAvailable ? 'Aktif' : 'Mode Browser' }}
                </span>
            </div>

            <form method="POST" action="{{ route('settings.save_printers') }}" class="space-y-md p-lg">
                @csrf
                <div>
                    <label class="tb-label" for="printer_kasir_select">Printer Kasir Thermal</label>
                    <select id="printer_kasir_select" name="printer_kasir" class="tb-control">
                        <option value="">Gunakan printer default</option>
                        @foreach ($availablePrinters as $printer)
                            <option value="{{ $printer }}" @selected($printerKasir === $printer)>{{ $printer }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="tb-label" for="printer_kantor_select">Printer Kantor A4</label>
                    <select id="printer_kantor_select" name="printer_kantor" class="tb-control">
                        <option value="">Gunakan printer default</option>
                        @foreach ($availablePrinters as $printer)
                            <option value="{{ $printer }}" @selected($printerKantor === $printer)>{{ $printer }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="tb-btn tb-btn-primary"><span class="material-symbols-outlined">save</span>Simpan Printer</button>
                </div>
            </form>

            <div class="flex flex-wrap gap-sm border-t border-outline-variant p-lg">
                <form method="POST" action="{{ route('settings.test_printer') }}">
                    @csrf
                    <input type="hidden" name="printer_type" value="kasir">
                    <button type="submit" class="tb-btn tb-btn-secondary"><span class="material-symbols-outlined">receipt_long</span>Tes Printer Kasir</button>
                </form>
                <form method="POST" action="{{ route('settings.test_printer') }}">
                    @csrf
                    <input type="hidden" name="printer_type" value="kantor">
                    <button type="submit" class="tb-btn tb-btn-secondary"><span class="material-symbols-outlined">print</span>Tes Printer A4</button>
                </form>
            </div>
        </section>

        <section class="tb-card">
            <div class="tb-card-header">
                <div>
                    <h3 class="tb-card-title">Folder Data & Cadangan</h3>
                    <p class="tb-card-subtitle">Folder ini dipakai untuk backup dan ekspor. Database aktif NativePHP tetap berada pada AppData aplikasi.</p>
                </div>
            </div>
            <div class="space-y-md p-lg">
                <div>
                    <label class="tb-label">Folder Terpilih</label>
                    <div class="rounded-xl border border-outline-variant bg-surface-container-low p-md font-mono text-body-md text-on-surface">{{ $dataDirectory ?: 'Belum dipilih' }}</div>
                </div>
                <form method="POST" action="{{ route('settings.choose_directory') }}">
                    @csrf
                    <button type="submit" class="tb-btn tb-btn-primary"><span class="material-symbols-outlined">folder_open</span>Pilih Folder</button>
                </form>
                <p class="text-label-md text-on-surface-variant">Dialog pemilihan folder berjalan secara lokal dan tidak memerlukan internet.</p>
            </div>
        </section>

        <section class="tb-card xl:col-span-2">
            <div class="tb-card-header">
                <div>
                    <h3 class="tb-card-title">Backup & Restore</h3>
                    <p class="tb-card-subtitle">SQLite digunakan otomatis pada build NativePHP. MySQL tetap didukung saat mode web lokal.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-lg p-lg lg:grid-cols-2">
                <div class="rounded-xl border border-outline-variant bg-surface-container-low p-lg">
                    <div class="mb-md flex items-center gap-md">
                        <span class="material-symbols-outlined text-3xl text-primary">backup</span>
                        <div><h4 class="font-bold text-on-surface">Buat Cadangan</h4><p class="text-body-md text-on-surface-variant">Simpan salinan database ke folder terpilih.</p></div>
                    </div>
                    <form method="POST" action="{{ route('settings.backup') }}">
                        @csrf
                        <button type="submit" class="tb-btn tb-btn-primary"><span class="material-symbols-outlined">save</span>Backup Sekarang</button>
                    </form>
                </div>

                <div class="rounded-xl border border-outline-variant bg-surface-container-low p-lg">
                    <div class="mb-md flex items-center gap-md">
                        <span class="material-symbols-outlined text-3xl text-error">restore</span>
                        <div><h4 class="font-bold text-on-surface">Pulihkan Cadangan</h4><p class="text-body-md text-on-surface-variant">Pilih file lokal lalu mulai ulang aplikasi.</p></div>
                    </div>
                    <form method="POST" action="{{ route('settings.restore') }}" onsubmit="return confirm('Restore akan menimpa database aktif. Lanjutkan?')">
                        @csrf
                        <button type="submit" class="tb-btn tb-btn-danger"><span class="material-symbols-outlined">restore_page</span>Pilih dan Restore</button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-sm border-t border-outline-variant p-lg text-body-md md:grid-cols-2">
                <div><span class="text-on-surface-variant">Backup terakhir:</span> <strong>{{ $lastBackupTime }}</strong></div>
                <div class="truncate"><span class="text-on-surface-variant">Lokasi:</span> <strong>{{ $lastBackupPath }}</strong></div>
                <div><span class="text-on-surface-variant">Ukuran:</span> <strong>{{ $lastBackupSize }}</strong></div>
                <div><span class="text-on-surface-variant">Restore terakhir:</span> <strong>{{ $lastRestoreTime }}</strong></div>
            </div>
        </section>
    </div>
</div>
@endsection
