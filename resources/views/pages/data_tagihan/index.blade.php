@extends('layouts.app')

@section('title', 'Data Tagihan Sales | Toko Bangunan 39')

@section('content')
<!-- Wrapper Halaman Internal -->
<div class="p-lg space-y-lg flex-1">

    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-md border-b border-outline-variant/60 pb-md">
        <div>
            <h2 class="font-headline-xl text-headline-xl text-on-surface uppercase text-primary font-bold tracking-tight">Data Tagihan Sales</h2>
            <p class="text-xs text-on-surface-variant mt-0.5">Kelola penugasan penagihan sales, cetak surat jalan tagihan harian, dan proses setoran pelunasan invoice.</p>
        </div>
        
        <div class="flex justify-end gap-2">
            <button type="button" id="btn-export-excel" class="flex items-center gap-1 border border-emerald-600 bg-white text-emerald-700 px-4 py-2 rounded-xl text-xs font-bold hover:bg-emerald-50 shadow-sm transition-all">
                <span>Export Excel</span>
            </button>
            <a class="flex items-center gap-1 bg-primary text-on-primary px-4 py-2 rounded-xl text-xs font-bold hover:brightness-110 shadow-sm transition-all" href="{{ route('data-tagihan.create') }}">
                <span>Buat Tagihan Baru</span>
            </a>
        </div>
    </div>

    <!-- Grid Kartu Summary (Clean & Minimalist) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-md">
        <div class="bg-surface-container-lowest border border-outline-variant p-md rounded-xl shadow-sm space-y-1">
            <p class="text-on-surface-variant font-bold text-[11px] uppercase tracking-wider">Total Lembaran Tagihan</p>
            <h3 class="text-xl font-black text-primary">{{ number_format($totalSheets, 0, ',', '.') }} Dokumen</h3>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant p-md rounded-xl shadow-sm space-y-1">
            <p class="text-on-surface-variant font-bold text-[11px] uppercase tracking-wider">Nominal Tagihan Dibawa</p>
            <h3 class="text-xl font-black text-amber-600">Rp {{ number_format($totalNominalDibawa, 0, ',', '.') }}</h3>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant p-md rounded-xl shadow-sm space-y-1">
            <p class="text-on-surface-variant font-bold text-[11px] uppercase tracking-wider">Total Setoran Terkumpul</p>
            <h3 class="text-xl font-black text-emerald-600">Rp {{ number_format($totalNominalSelesai, 0, ',', '.') }}</h3>
        </div>
    </div>

    <!-- Panel Tabel Ledger -->
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-sm">
        <!-- Area Filter Realtime (Clean Borderless Inputs) -->
        <div class="px-4 py-3 border-b border-outline-variant flex flex-wrap items-center justify-between gap-3 bg-surface-container-low/40">
            <!-- Filter Left: Rentang Tanggal + Sales + Status -->
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <!-- RENTANG TANGGAL (Soft Border Tanpa Teks Dari/Sampai) -->
                <div class="flex items-center gap-1.5">
                    <input type="date" id="filter-tgl-dari" class="h-9 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-on-surface outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 shadow-none cursor-pointer">
                    <span class="text-slate-400 text-xs font-medium">-</span>
                    <input type="date" id="filter-tgl-sampai" class="h-9 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-on-surface outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 shadow-none cursor-pointer">
                </div>

                <!-- FILTER SALESMAN -->
                <select id="filter-sales" class="h-9 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-on-surface outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 shadow-none">
                    <option value="">-- Semua Salesman --</option>
                    @foreach($salesmen as $s)
                        <option value="{{ $s->id }}">{{ $s->nama_sales }} ({{ $s->kode_sales }})</option>
                    @endforeach
                </select>

                <!-- FILTER STATUS -->
                <select id="filter-status" class="h-9 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-on-surface outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 shadow-none">
                    <option value="">-- Semua Status --</option>
                    <option value="DIBAWA">DIBAWA</option>
                    <option value="SELESAI">SELESAI</option>
                </select>
            </div>

            <!-- Filter Right: PENCARIAN REALTIME (Lapang & Soft Border) -->
            <div class="relative w-full sm:w-64 min-w-[200px]">
                <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-base text-on-surface-variant">search</span>
                <input type="text" id="filter-search" placeholder="Cari No. DO, toko..." class="h-9 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-3 text-xs font-medium outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 shadow-none" autocomplete="off">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant text-on-surface-variant font-bold">
                        <th class="px-4 py-3">No. DO Tagihan</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Salesman</th>
                        <th class="px-4 py-3 text-center">Jumlah Nota</th>
                        <th class="px-4 py-3 text-right">Total Tagihan</th>
                        <th class="px-4 py-3 text-right">Total Setoran</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tagihan-table-body" class="divide-y divide-outline-variant/60">
                    @forelse($tagihans as $item)
                        @php
                            $tglIso = optional($item->tgl_dt)->format('Y-m-d');
                        @endphp
                        <tr class="tagihan-row hover:bg-surface-container-low/30 transition-colors" 
                            data-tanggal="{{ $tglIso }}"
                            data-sales="{{ strtolower($item->salesman_name) }}"
                            data-sales-id="{{ $item->sales_id }}"
                            data-status="{{ $item->status }}"
                            data-search="{{ strtolower($item->no_do . ' ' . $item->salesman_name) }}">
                            
                            <td class="px-4 py-3.5 font-bold text-primary font-mono tracking-wide">
                                {{ $item->no_do }}
                            </td>
                            <td class="px-4 py-3.5 text-on-surface-variant">
                                {{ optional($item->tgl_dt)->translatedFormat('d M Y') }}
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-on-surface">
                                {{ $item->salesman_name }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-surface-container-high text-on-surface-variant uppercase tracking-wider">
                                    {{ $item->details->count() }} Toko
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-on-surface">
                                Rp {{ number_format($item->total_tagihan, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-emerald-600">
                                @if($item->status === 'SELESAI')
                                    Rp {{ number_format($item->total_bayar, 0, ',', '.') }}
                                @else
                                    <span class="text-on-surface-variant/60 font-normal">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if($item->status === 'SELESAI')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase tracking-wider">
                                        SELESAI
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800 uppercase tracking-wider">
                                        DIBAWA SALES
                                    </span>
                                @endif
                            </td>
                            
                            <!-- Tombol Aksi Trigger -->
                            <td class="px-4 py-3.5 text-center">
                                <button type="button" 
                                        class="btn-aksi-trigger flex items-center justify-center gap-1 border border-slate-200 bg-white px-3 py-1.5 rounded-lg text-on-surface hover:bg-slate-50 text-xs font-bold transition-colors mx-auto"
                                        data-show-url="{{ route('data-tagihan.show', $item->id) }}"
                                        data-print-url="{{ route('data-tagihan.print', $item->id) }}"
                                        data-settle-url="{{ route('data-tagihan.settle', $item->id) }}"
                                        data-delete-url="{{ route('data-tagihan.destroy', $item->id) }}"
                                        data-status="{{ $item->status }}">
                                    <span>Aksi</span>
                                    <span class="text-[9px] text-slate-400">▼</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr id="empty-row-server">
                            <td colspan="8" class="px-4 py-8 text-center text-on-surface-variant font-medium">
                                Belum ada dokumen Data Tagihan Sales.
                            </td>
                        </tr>
                    @endforelse

                    <tr id="no-filter-results" class="hidden">
                        <td colspan="8" class="px-4 py-8 text-center text-on-surface-variant font-medium">
                            Tidak ada data tagihan yang sesuai dengan filter/tanggal yang dipilih.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if($tagihans->hasPages())
            <div class="px-4 py-3 border-t border-outline-variant bg-surface-container-low/20">
                {{ $tagihans->links() }}
            </div>
        @endif
    </div>
</div>

<!-- GLOBAL FLOATING DROPDOWN MENU (Pop-up Melayang di Paling Luar HTML Body) -->
<div id="global-action-dropdown" class="hidden fixed z-[99999] w-36 rounded-xl bg-white shadow-2xl border border-slate-100 py-1 font-sans text-xs transition-all duration-75">
    <a id="drop-link-detail" href="#" class="block px-4 py-2 text-slate-700 hover:bg-slate-50 hover:text-primary font-medium text-left">
        Detail
    </a>
    <a id="drop-link-print" href="#" target="_blank" class="block px-4 py-2 text-slate-700 hover:bg-slate-50 hover:text-primary font-medium text-left">
        Print
    </a>
    <a id="drop-link-setoran" href="#" class="block px-4 py-2 text-emerald-600 hover:bg-emerald-50 font-semibold text-left">
        Setoran
    </a>
    <form id="drop-form-delete" action="#" method="POST" onsubmit="return confirm('Hapus lembar tagihan ini?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="w-full block px-4 py-2 text-red-600 hover:bg-red-50 font-semibold text-left">
            Hapus
        </button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const inputTglDari = document.getElementById('filter-tgl-dari');
    const inputTglSampai = document.getElementById('filter-tgl-sampai');
    const selectSales = document.getElementById('filter-sales');
    const selectStatus = document.getElementById('filter-status');
    const inputSearch = document.getElementById('filter-search');
    const rows = document.querySelectorAll('.tagihan-row');
    const noResultsRow = document.getElementById('no-filter-results');
    const exportButton = document.getElementById('btn-export-excel');

    // 1. Filter Real-time
    function applyRealtimeFilters() {
        const selectedDari = inputTglDari ? inputTglDari.value : '';
        const selectedSampai = inputTglSampai ? inputTglSampai.value : '';
        const selectedSales = selectSales ? selectSales.value : '';
        const selectedStatus = selectStatus ? selectStatus.value : '';
        const searchText = inputSearch ? inputSearch.value.toLowerCase().trim() : '';

        let visibleCount = 0;

        rows.forEach(row => {
            const rowTanggal = row.getAttribute('data-tanggal');
            const rowSales = row.getAttribute('data-sales-id');
            const rowStatus = row.getAttribute('data-status');
            const rowSearch = row.getAttribute('data-search');

            let matchTanggal = true;
            if (selectedDari && rowTanggal < selectedDari) matchTanggal = false;
            if (selectedSampai && rowTanggal > selectedSampai) matchTanggal = false;

            const matchSales = !selectedSales || rowSales === selectedSales;
            const matchStatus = !selectedStatus || rowStatus === selectedStatus;
            const matchSearch = !searchText || rowSearch.includes(searchText);

            if (matchTanggal && matchSales && matchStatus && matchSearch) {
                row.classList.remove('hidden');
                visibleCount++;
            } else {
                row.classList.add('hidden');
            }
        });

        if (noResultsRow) {
            if (visibleCount === 0 && rows.length > 0) {
                noResultsRow.classList.remove('hidden');
            } else {
                noResultsRow.classList.add('hidden');
            }
        }
    }

    if (inputTglDari) inputTglDari.addEventListener('change', applyRealtimeFilters);
    if (inputTglSampai) inputTglSampai.addEventListener('change', applyRealtimeFilters);
    if (selectSales) selectSales.addEventListener('change', applyRealtimeFilters);
    if (selectStatus) selectStatus.addEventListener('change', applyRealtimeFilters);
    if (inputSearch) inputSearch.addEventListener('input', applyRealtimeFilters);

    applyRealtimeFilters();

    if (exportButton) {
        exportButton.addEventListener('click', async function () {
            const salesId = selectSales ? selectSales.value : '';
            if (!salesId) {
                window.alert('Pilih salesman terlebih dahulu untuk export history.');
                if (selectSales) selectSales.focus();
                return;
            }

            const params = new URLSearchParams({ sales_id: salesId });
            if (inputTglDari && inputTglDari.value) params.set('tgl_dari', inputTglDari.value);
            if (inputTglSampai && inputTglSampai.value) params.set('tgl_sampai', inputTglSampai.value);

            const exportUrl = `{{ route('data-tagihan.export_excel', [], false) }}?${params.toString()}`;
            const salesName = selectSales.options[selectSales.selectedIndex].text
                .replace(/\s*\([^)]*\)\s*$/, '')
                .replace(/[^a-zA-Z0-9_-]+/g, '_')
                .replace(/^_+|_+$/g, '') || 'Sales';
            const dateStamp = new Date().toISOString().slice(0, 10).replaceAll('-', '');
            const suggestedName = `History_Tagihan_${salesName}_${dateStamp}.xls`;

            exportButton.disabled = true;
            exportButton.classList.add('opacity-60', 'cursor-wait');

            try {
                if ('showSaveFilePicker' in window) {
                    const fileHandle = await window.showSaveFilePicker({
                        suggestedName,
                        types: [{
                            description: 'Microsoft Excel 97-2003 Worksheet',
                            accept: {
                                'application/vnd.ms-excel': ['.xls'],
                            },
                        }],
                    });

                    const response = await fetch(exportUrl, {
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-Excel-Transport': 'base64',
                        },
                    });
                    const payload = await response.json().catch(() => null);

                    if (!response.ok || !payload || !payload.content) {
                        const serverMessage = payload?.message
                            || Object.values(payload?.errors || {}).flat()[0]
                            || (response.redirected && response.url.includes('/login')
                                ? 'Sesi login berakhir. Muat ulang halaman lalu login kembali.'
                                : 'Server gagal membuat file Excel.');
                        throw new Error(serverMessage);
                    }

                    const binaryString = window.atob(payload.content);
                    const fileBytes = Uint8Array.from(binaryString, character => character.charCodeAt(0));
                    const fileBlob = new Blob([fileBytes], {
                        type: 'application/vnd.ms-excel',
                    });

                    const writable = await fileHandle.createWritable();
                    await writable.write(fileBlob);
                    await writable.close();
                    window.alert('Excel history tagihan berhasil disimpan.');
                } else {
                    const downloadLink = document.createElement('a');
                    downloadLink.href = exportUrl;
                    downloadLink.download = suggestedName;
                    downloadLink.hidden = true;
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    downloadLink.remove();
                }
            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error(error);
                    window.alert(error.message || 'Gagal menyimpan Excel history tagihan.');
                }
            } finally {
                exportButton.disabled = false;
                exportButton.classList.remove('opacity-60', 'cursor-wait');
            }
        });
    }

    // 2. TELEPORT GLOBAL ACTION DROPDOWN (Dijamin Melayang 100% Bebas Terpotong Frame)
    const globalDropdown = document.getElementById('global-action-dropdown');
    const dropDetail = document.getElementById('drop-link-detail');
    const dropPrint = document.getElementById('drop-link-print');
    const dropSetoran = document.getElementById('drop-link-setoran');
    const dropFormDelete = document.getElementById('drop-form-delete');

    function closeGlobalDropdown() {
        if (globalDropdown) globalDropdown.classList.add('hidden');
    }

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-aksi-trigger');
        if (btn) {
            e.stopPropagation();
            
            const showUrl = btn.getAttribute('data-show-url');
            const printUrl = btn.getAttribute('data-print-url');
            const settleUrl = btn.getAttribute('data-settle-url');
            const deleteUrl = btn.getAttribute('data-delete-url');
            const status = btn.getAttribute('data-status');

            if (dropDetail) dropDetail.href = showUrl;
            if (dropPrint) dropPrint.href = printUrl;

            if (status === 'DIBAWA') {
                if (dropSetoran) {
                    dropSetoran.href = settleUrl;
                    dropSetoran.classList.remove('hidden');
                }
                if (dropFormDelete) {
                    dropFormDelete.action = deleteUrl;
                    dropFormDelete.classList.remove('hidden');
                }
            } else {
                if (dropSetoran) dropSetoran.classList.add('hidden');
                if (dropFormDelete) dropFormDelete.classList.add('hidden');
            }

            // Hitung posisi relatif terhadap Layar Viewport (Fixed)
            const rect = btn.getBoundingClientRect();
            const viewportHeight = window.innerHeight;
            const menuHeight = status === 'DIBAWA' ? 145 : 80;
            const menuWidth = 144; // 9rem / w-36

            let leftPos = rect.right - menuWidth;
            if (leftPos < 10) leftPos = 10;

            let topPos;
            if (viewportHeight - rect.bottom >= menuHeight || rect.top < menuHeight) {
                topPos = rect.bottom + 4; // Buka ke bawah
            } else {
                topPos = rect.top - menuHeight - 4; // Buka ke atas
            }

            globalDropdown.style.left = `${leftPos}px`;
            globalDropdown.style.top = `${topPos}px`;
            globalDropdown.classList.remove('hidden');
        } else if (!e.target.closest('#global-action-dropdown')) {
            closeGlobalDropdown();
        }
    });

    window.addEventListener('scroll', closeGlobalDropdown, true);
    window.addEventListener('resize', closeGlobalDropdown);
});
</script>
@endsection
