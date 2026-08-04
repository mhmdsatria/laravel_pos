@extends('layouts.app')

@section('title', 'Laporan Stok Barang | Toko Bangunan 39')
@section('page_title', 'Laporan Stok Barang')
@section('active_page', 'inventory_report')

@push('styles')
<style>
tr.hidden-row {
    display: none !important;
}

.toggle-switch:checked + .toggle-label {
    background-color: #d1fae5;
}

.toggle-switch:checked ~ .toggle-dot {
    transform: translateX(100%);
    background-color: #006c49;
}
</style>
@endpush

@section('content')
<div class="space-y-lg">
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-md">
        <div>
            <h2 class="uppercase text-primary text-headline-xl font-headline-xl text-on-surface tracking-tight">
                Laporan Stok Barang Real-time
            </h2>
            <p class="text-body-md font-body-md text-outline mt-1">
                Data inventaris gudang diperbarui dari tabel produk dan stok berjalan.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-sm">
            <a id="btnExportExcel" href="{{ route('inventory_report.export_excel', request()->query()) }}"
                class="flex items-center justify-center gap-sm px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-lg text-label-md font-label-md text-on-surface hover:bg-surface-container-high transition-colors">
                <span class="material-symbols-outlined text-[18px]">file_export</span>
                EXPORT EXCEL
            </a>

            <a id="btnExportPdf" href="{{ route('inventory_report.export_pdf', request()->query()) }}"
                class="flex items-center justify-center gap-sm px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-lg text-label-md font-label-md text-on-surface hover:bg-surface-container-high transition-colors">
                <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span>
                EXPORT PDF
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-gutter">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-lg shadow-sm">
            <p class="text-label-md font-label-md text-outline mb-1">Total Jenis Barang</p>
            <h3 class="text-headline-lg font-headline-lg text-on-surface">
                {{ number_format((int) $totalJenisBarang, 0, ',', '.') }} SKU
            </h3>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-lg shadow-sm">
            <p class="text-label-md font-label-md text-outline mb-1">Stok Tersedia</p>
            <h3 class="text-headline-lg font-headline-lg text-on-surface">
                {{ format_qty($totalStokTersedia) }} Unit
            </h3>
        </div>

        <div class="bg-tertiary-container/10 border border-tertiary-container/30 rounded-xl p-lg shadow-sm">
            <p class="text-label-md font-label-md text-tertiary mb-1">Stok Habis / Kritis</p>
            <h3 class="text-headline-lg font-headline-lg text-on-tertiary-container">
                {{ number_format((int) $totalStokKritis, 0, ',', '.') }} Item
            </h3>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-lg shadow-sm">
            <p class="text-label-md font-label-md text-outline mb-1">Update Terakhir</p>
            <h3 class="text-headline-lg font-headline-lg text-on-surface" id="clock-display">
                {{ $lastUpdate->format('H:i:s') }}
            </h3>
        </div>
    </div>

    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-sm overflow-hidden">
        <div class="flex flex-col gap-md border-b border-outline-variant p-lg xl:flex-row xl:items-center xl:justify-between">
            <form 
    id="inventorySearchForm" 
    method="GET" 
    action="{{ route('inventory_report.index') }}"
    class="relative block w-full max-w-[440px] shrink-0 xl:w-[440px]"
>
    <span class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 z-10 -translate-y-1/2 text-[20px] leading-[20px] text-on-surface-variant">
        search
    </span>

    <input 
        id="tableSearch" 
        name="search" 
        type="text" 
        value="{{ $search }}" 
        placeholder="Cari kode atau nama barang..."
        autocomplete="off"
        class="block h-11 w-full min-w-0 rounded-full border border-outline-variant bg-surface-container-low pl-12 pr-4 text-body-md text-on-surface outline-none transition-all placeholder:text-on-surface-variant/60 focus:border-primary focus:ring-4 focus:ring-primary/10"
    >
    
    <!-- <button type="submit" class="sr-only">Cari</button> -->
    
    {{-- PHASE02_INVENTORY_SEARCH_SUBMIT --}}
</form>

            <div class="flex w-fit shrink-0 items-center gap-md rounded-full border border-outline-variant bg-surface-container-low px-md py-sm">
                <label class="flex cursor-pointer select-none items-center gap-sm text-label-md font-label-md text-on-surface-variant"
                    for="outOfStockToggle">
                    <span class="material-symbols-outlined text-[18px] text-tertiary">error</span>
                    <span>Tampilkan Stok Habis</span>
                </label>

                <div class="relative inline-block h-6 w-10 shrink-0">
                    <input id="outOfStockToggle" type="checkbox" name="out_of_stock" value="1" form="inventorySearchForm" @checked($onlyOutOfStock ?? false)
                        class="toggle-switch absolute z-10 block h-6 w-6 cursor-pointer appearance-none rounded-full border-4 border-outline-variant bg-white outline-none transition-transform duration-200">
                    <label for="outOfStockToggle" class="toggle-label block h-6 cursor-pointer overflow-hidden rounded-full bg-surface-variant transition-colors"></label>
                    <div class="toggle-dot pointer-events-none absolute left-0 top-0 h-6 w-6 rounded-full bg-outline-variant transition-transform duration-200"></div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse" id="inventoryTable">
                <thead>
                    <tr class="bg-surface-container-low">
                        <th class="px-lg py-md text-label-md font-label-md text-outline border-b border-outline-variant">Kode Barang</th>
                        <th class="px-lg py-md text-label-md font-label-md text-outline border-b border-outline-variant">Nama Barang</th>
                        <th class="px-lg py-md text-label-md font-label-md text-outline border-b border-outline-variant">Satuan</th>
                        <th class="px-lg py-md text-label-md font-label-md text-outline border-b border-outline-variant text-right">Stok Saat Ini</th>
                        <th class="px-lg py-md text-label-md font-label-md text-outline border-b border-outline-variant text-center">Status Stok</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant" id="inventoryTableBody">
                    @forelse ($products as $item)
                        @php
                            $stock = (float) ($item->sisa_stok ?? 0);
                            $limit = (float) ($item->limit_minimum_stok ?? 0);
                            $isOut = $stock <= 0;
                            $isCritical = $stock > 0 && $limit > 0 && $stock <= $limit;

                            $rowClass = $isOut
                                ? 'bg-error-container/5 hover:bg-error-container/10'
                                : ($isCritical
                                    ? 'bg-amber-50/80 hover:bg-amber-100/80'
                                    : 'hover:bg-surface-container-low');

                            $stockClass = $isOut
                                ? 'text-error'
                                : ($isCritical ? 'text-amber-600' : 'text-primary');
                        @endphp
                        <tr class="{{ $rowClass }} transition-colors">
                            <td class="px-lg py-md text-body-md font-mono text-on-surface-variant">{{ $item->kode_barang }}</td>
                            <td class="px-lg py-md text-body-md font-bold text-on-surface">{{ $item->nama_barang }}</td>
                            <td class="px-lg py-md text-body-md text-on-surface-variant">{{ $item->satuan ?? '-' }}</td>
                            <td class="px-lg py-md text-body-md font-black text-right {{ $stockClass }}">{{ format_qty($stock) }}</td>
                            <td class="px-lg py-md text-center">
                                @if ($isOut)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black bg-error-container text-error uppercase tracking-wider">STOK HABIS</span>
                                @elseif ($isCritical)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-100 text-amber-700 uppercase tracking-wider">KRITIS</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black bg-primary-container/20 text-primary uppercase tracking-wider">TERSEDIA</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-lg py-xl text-center text-on-surface-variant">Belum ada data barang pada master produk.</td>
                        </tr>
                    @endforelse
                </tbody>
                {{-- PHASE02_INVENTORY_TFOOT_START --}}
                <tfoot>
                    <tr class="bg-surface-container-low border-t-2 border-outline-variant">
                        <td colspan="3" class="px-lg py-md text-label-md font-black text-on-surface uppercase tracking-wider">
                            Total hasil {{ ($inventorySummary['is_filtered'] ?? false) ? 'filter' : 'semua stok' }}
                        </td>
                        <td class="px-lg py-md text-body-md font-black text-right text-primary">
                            {{ format_qty($inventorySummary['total_stok_tersedia'] ?? 0) }}
                        </td>
                        <td class="px-lg py-md text-center text-label-md font-bold text-on-surface-variant">
                            {{ number_format((int) ($inventorySummary['total_jenis_barang'] ?? 0), 0, ',', '.') }} SKU
                        </td>
                    </tr>
                    <!-- <tr class="bg-surface-container-lowest">
                        <td colspan="5" class="px-lg py-sm text-xs text-on-surface-variant">
                            Nilai modal stok: <strong class="text-on-surface">Rp {{ number_format((float) ($inventorySummary['total_nilai_modal'] ?? 0), 0, ',', '.') }}</strong>
                            <span class="mx-2">�</span>
                            Nilai jual normal: <strong class="text-on-surface">Rp {{ number_format((float) ($inventorySummary['total_nilai_jual'] ?? 0), 0, ',', '.') }}</strong>
                        </td>
                    </tr> -->
                </tfoot>
                {{-- PHASE02_INVENTORY_TFOOT_END --}}
            </table>
        </div>

        <div class="px-lg py-md border-t border-outline-variant bg-surface-container-lowest">
            {{ $products->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
/* PHASE02_INVENTORY_SERVER_SEARCH_START
 * Search laporan stok sekarang memakai GET/server-side.
 * Efeknya: hasil tidak lagi terbatas pada 15 row page aktif, pagination dan daftar item selalu konsisten.
 */
const baseExportUrl = @json(route('inventory_report.export_pdf'));
const baseExportExcelUrl = @json(route('inventory_report.export_excel'));

function buildInventoryUrl(baseUrl, term, onlyOutOfStock) {
    const url = new URL(baseUrl, window.location.origin);

    if (term) {
        url.searchParams.set('search', term);
    }

    if (onlyOutOfStock) {
        url.searchParams.set('out_of_stock', '1');
    }

    return url.pathname + url.search;
}

function syncInventoryExportUrls() {
    const searchInput = document.getElementById('tableSearch');
    const toggle = document.getElementById('outOfStockToggle');
    const term = (searchInput ? searchInput.value : '').trim();
    const onlyOutOfStock = toggle ? toggle.checked : false;
    const pdfButton = document.getElementById('btnExportPdf');
    const excelButton = document.getElementById('btnExportExcel');

    if (pdfButton) {
        pdfButton.href = buildInventoryUrl(baseExportUrl, term, onlyOutOfStock);
    }

    if (excelButton) {
        excelButton.href = buildInventoryUrl(baseExportExcelUrl, term, onlyOutOfStock);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('inventorySearchForm');
    const searchInput = document.getElementById('tableSearch');
    const toggle = document.getElementById('outOfStockToggle');
    const clock = document.getElementById('clock-display');

    // Fungsi Eksekusi Pencarian
    function submitInventorySearch() {
        if (!searchForm) {
            return;
        }

        const term = (searchInput ? searchInput.value : '').trim();
        const onlyOutOfStock = toggle ? toggle.checked : false;
        window.location.href = buildInventoryUrl(searchForm.action, term, onlyOutOfStock);
    }

    // 1. Menangani saat Form di-Submit (Tekan Enter)
    if (searchForm) {
        searchForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Mencegah reload bawaan browser
            submitInventorySearch(); // Jalankan pencarian
        });
    }

    // 2. Menangani saat Mengetik di Kolom Pencarian
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            syncInventoryExportUrls(); 
            // Oomatisasi pencarian (searchTimer) di sini SEKARANG DIHAPUS
        });
    }

    // 3. Menangani saat Toggle Stok Kosong Berubah
    if (toggle) {
        toggle.addEventListener('change', function() {
            syncInventoryExportUrls();
            submitInventorySearch(); // Jika toggle diklik, langsung cari
        });
    }

    // 4. Fitur Jam Digital (Clock)
    if (clock) {
        setInterval(function() {
            const now = new Date();
            clock.innerText = now.toLocaleTimeString('id-ID', { hour12: false });
        }, 30000);
    }

    // Sinkronisasi awal saat halaman pertama dimuat
    syncInventoryExportUrls();
});
/* PHASE02_INVENTORY_SERVER_SEARCH_END */
</script>
@endpush