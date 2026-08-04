@extends('layouts.app')

@section('title', 'Master Produk | Toko Bangunan 39')

@section('content')
<div class="space-y-lg">
    <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4">
        <div>
            <h3 class="font-headline-xl text-headline-xl text-on-surface uppercase text-primary font-bold">Master Produk</h3>
            <p class="text-body-md text-on-surface-variant mt-1">Kelola material bangunan, stok aktif, harga modal, dan batas minimum restock.</p>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-sm w-full xl:w-auto">
            {{-- FORM IMPORT DENGAN ID EXPLICIT --}}
            <form id="excelImportForm" method="POST" action="{{ route('product.import') }}" enctype="multipart/form-data" class="w-full sm:w-auto">
                @csrf
                <label class="tb-btn tb-btn-secondary w-full cursor-pointer sm:w-auto">
                    <span class="material-symbols-outlined">upload_file</span>
                    <span class="font-label-md text-label-md uppercase tracking-wider">Import Excel</span>
                    <input type="file" id="excelFileInput" name="excel_file" accept=".xlsx,.xls,.csv" class="hidden">
                </label>
            </form>

            <button id="openProductCreateBtn" data-open-product-modal="true" class="tb-btn tb-btn-primary cursor-pointer" onclick="openCreateProductModal()" type="button">
                <span class="material-symbols-outlined">add</span>
                <span class="font-label-md text-label-md uppercase tracking-wider">Tambah Produk</span>
            </button>
        </div>
    </div>

    {{-- Main Index Table Block --}}
    <div class="overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest shadow-sm">
        <div class="flex flex-col gap-md border-b border-outline-variant bg-surface-container-lowest px-lg py-md lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="font-headline-md text-headline-md text-on-surface font-bold">Daftar Produk</p>
                <p class="mt-1 text-label-md text-on-surface-variant">Cari berdasarkan kode atau nama produk.</p>
            </div>

            <form
                method="GET"
                action="{{ route('product.index') }}"
                id="productSearchForm"
                class="flex w-full flex-col gap-sm sm:flex-row lg:w-auto"
            >
                <div class="relative w-full lg:w-[440px]">
                    <span class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 z-10 -translate-y-1/2 text-on-surface-variant" style="font-size:20px;line-height:20px;">
                        search
                    </span>

                    <input
                        id="globalSearch"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Cari kode atau nama produk..."
                        type="text"
                        autocomplete="off"
                        class="block h-11 w-full rounded-full border border-outline-variant bg-surface-container-low pl-12 pr-4 text-body-md text-on-surface outline-none transition-all placeholder:text-on-surface-variant/60 focus:border-primary focus:ring-4 focus:ring-primary/10"
                    >
                </div>

                <button type="submit" class="tb-btn tb-btn-primary shrink-0 cursor-pointer">
                    <span class="material-symbols-outlined text-[19px]">search</span>
                    <span>Cari</span>
                </button>

                @if ($search !== '')
                    <a href="{{ route('product.index') }}" class="tb-btn tb-btn-secondary shrink-0">
                        <span class="material-symbols-outlined text-[19px]">close</span>
                        <span>Reset</span>
                    </a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="tb-table min-w-[980px]" id="productTable">
                <thead class="bg-surface-container-low text-label-md text-on-surface-variant uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3 w-12 text-center">No</th>
                        <th class="px-4 py-3">Kode Barang</th>
                        <th class="px-4 py-3">Nama Barang</th>
                        <th class="px-4 py-3">Satuan</th>
                        <th class="px-4 py-3 text-right">Harga Modal</th>
                        <!-- <th class="px-4 py-3 text-right">Harga Jual Normal</th> -->
                        <th class="px-4 py-3 text-center">Stok Aktif</th>
                        <th class="px-4 py-3 text-center">Limit Min</th>
                        <th class="px-4 py-3 text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-body-md divide-y divide-outline-variant">
                    @forelse ($products as $key => $item)
                        <tr class="transition-colors group {{ $item->sisa_stok <= $item->limit_minimum_stok ? 'bg-error/5 text-error hover:bg-error/10' : 'hover:bg-surface-container-low text-on-surface' }}">
                            <td class="px-4 py-3 text-center text-on-surface-variant">{{ $products->firstItem() + $key }}</td>
                            <td class="px-4 py-3 font-mono text-xs font-semibold">{{ $item->kode_barang ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="font-bold {{ $item->sisa_stok <= $item->limit_minimum_stok ? 'text-error' : 'text-primary' }}">{{ $item->nama_barang }}</div>
                                <!-- @if ($item->deskripsi)
                                    <div class="text-[11px] text-on-surface-variant truncate max-w-sm mt-0.5">{{ $item->deskripsi }}</div>
                                @endif -->
                            </td>
                            <td class="px-4 py-3">{{ $item->satuan }}</td>
                            <td class="px-4 py-3 text-right font-semibold">Rp {{ number_format($item->harga_beli_terakhir, 0, ',', '.') }}</td>
                            <!-- <td class="px-4 py-3 text-right font-bold text-primary">Rp {{ number_format($item->harga_jual_normal ?? 0, 0, ',', '.') }}</td> -->
                            <td class="px-4 py-3 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $item->sisa_stok <= $item->limit_minimum_stok ? 'bg-error-container text-error' : 'bg-primary/10 text-primary' }}">
                                    {{ format_qty($item->sisa_stok) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center font-medium">{{ format_qty($item->limit_minimum_stok) }}</td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button class="p-1 hover:text-primary transition-colors cursor-pointer" title="Lihat" type="button" onclick="openShowProductModal({{ $item->id }})">
                                        <span class="material-symbols-outlined text-xl">visibility</span>
                                    </button>
                                    <button class="p-1 hover:text-primary transition-colors cursor-pointer" title="Edit" type="button" onclick="openEditProductModal({{ $item->id }})">
                                        <span class="material-symbols-outlined text-xl">edit</span>
                                    </button>
                                    <form method="POST" action="{{ route('product.destroy', $item->id) }}" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data produk ' + '{{ addslashes($item->nama_barang) }}' + '?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="p-1 text-error hover:scale-105 transition-all cursor-pointer" title="Hapus" type="submit">
                                            <span class="material-symbols-outlined text-xl">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-on-surface-variant">
                                <span class="material-symbols-outlined text-[44px] text-outline mb-2">inventory_2</span>
                                <p class="font-headline-md text-headline-md font-bold">Produk Tidak Ditemukan</p>
                                <p class="text-body-md mt-1">Tidak ada produk yang cocok dengan pencarian keyword Anda.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-outline-variant flex flex-col lg:flex-row gap-3 justify-between lg:items-center bg-surface-container-lowest">
            <p class="text-label-md text-on-surface-variant">
                Showing {{ $products->firstItem() ?? 0 }} to {{ $products->lastItem() ?? 0 }} of {{ $products->total() }} products
            </p>
            <div class="pagination-wrapper text-sm">
                {{ $products->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@section('modals')
@stop {{-- Mencegah bypass penumpukan section bawaan layout --}}

@push('modals')
{{-- FIX MODAL UTAMA: Clean, Modern, Elit Layout --}}
<div class="fixed inset-0 z-[60] backdrop-blur-sm bg-black/40 items-center justify-center p-4 hidden" id="productModal">
    <div class="relative w-full max-w-5xl max-h-[88vh] bg-white rounded-2xl shadow-2xl overflow-hidden border border-outline-variant flex flex-col transition-all duration-300 animate-in zoom-in-95">
        
        {{-- Header Modal --}}
        <div class="flex justify-between items-center px-lg py-md border-b border-outline-variant/60 bg-surface-container-lowest shrink-0">
            <div class="flex items-center gap-3">
                <!-- <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center text-primary shadow-sm">
                    <span class="material-symbols-outlined text-2xl" id="productModalIcon">add_box</span>
                </div> -->
                <div>
                    <h4 class="text-right font-bold text-on-surface leading-tight" id="productModalTitle">Tambah Produk Baru</h4>
                    <p class="text-xs text-on-surface-variant mt-0.5" id="productModalSubtitle">Input satu atau banyak produk material bangunan.</p>
                </div>
            </div>
            <button class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-error/10 hover:text-error transition-colors cursor-pointer" onclick="closeProductModal()" type="button">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        {{-- Form Scroll Area --}}
        <form class="flex-1 overflow-y-auto p-lg space-y-lg bg-surface-container-lowest" id="productForm" method="POST" action="{{ route('product.store') }}">
            @csrf
            <div id="methodContainer"></div>
            
            <div id="productRows" class="space-y-md">
                {{-- Row Card Container --}}
                <div class="product-row border border-outline-variant rounded-xl bg-white overflow-hidden shadow-sm" data-row-index="0">
                    <div class="flex justify-between items-center px-md py-2.5 bg-surface-container-low/50 border-b border-outline-variant/60">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-xl">inventory_2</span>
                            <span class="text-xs font-bold uppercase tracking-wider text-on-surface row-title">Produk #1</span>
                        </div>
                        <button class="remove-row-btn hidden text-error hover:bg-error/10 rounded-full p-1.5 transition-colors cursor-pointer" type="button" onclick="removeProductRow(this)">
                            <span class="material-symbols-outlined text-base">close</span>
                        </button>
                    </div>

                    {{-- Form Grid Pembaruan Modern --}}
                    <div class="p-lg space-y-md">
                        {{-- Baris 1: Pengaturan Kode & Nama --}}
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-md">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">Mode Kode</label>
                                <div class="grid grid-cols-2 gap-0.5 rounded-xl bg-surface-container-high p-1 border border-outline-variant/40">
                                    <label class="kode-mode-label flex items-center justify-center py-1.5 rounded-lg cursor-pointer bg-primary text-on-primary font-bold text-xs transition-all">
                                        <input class="hidden kode-mode-radio" name="kode_mode[]" type="radio" value="otomatis" checked>
                                        <span>Auto</span>
                                    </label>
                                    <label class="kode-mode-label flex items-center justify-center py-1.5 rounded-lg cursor-pointer text-on-surface-variant font-bold text-xs transition-all">
                                        <input class="hidden kode-mode-radio" name="kode_mode[]" type="radio" value="manual">
                                        <span>Manual</span>
                                    </label>
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">Kode Barang</label>
                                <input class="kode-barang-input w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 py-2 text-xs font-mono text-on-surface-variant cursor-not-allowed outline-none" name="kode_barang[]" readonly type="text" value="{{ $nextKode }}" data-auto-value="{{ $nextKode }}">
                            </div>
                            <div class="space-y-1.5 md:col-span-2">
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">Nama Barang <span class="text-error font-normal">*</span></label>
                                <input class="nama-barang-input w-full bg-white border border-outline-variant rounded-xl px-4 py-2 text-xs font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none" name="nama_barang[]" placeholder="Contoh: Semen Tiga Roda 50kg" required type="text">
                            </div>
                        </div>

                        {{-- Baris 2: Deskripsi & Komoditas Satuan --}}
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-md">
                            <div class="space-y-1.5 md:col-span-3">
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">Deskripsi Singkat</label>
                                <input class="deskripsi-input w-full bg-white border border-outline-variant rounded-xl px-4 py-2 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none" name="deskripsi[]" placeholder="Keterangan tambahan spesifikasi produk...">
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">Satuan Komoditas <span class="text-error font-normal">*</span></label>
                                <select class="satuan-select w-full bg-white border border-outline-variant rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none cursor-pointer font-medium" name="satuan[]" required>
                                    <option value="Zak">Zak</option>
                                    <option value="Pcs">Pcs</option>
                                    <option value="Batang">Batang</option>
                                    <option value="Dus">Dus</option>
                                    <option value="__custom__">+ Input Satuan Baru...</option>
                                </select>
                                <input class="satuan-custom-input hidden w-full bg-white border border-primary rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-primary outline-none font-bold" placeholder="Ketik satuan baru..." type="text">
                            </div>
                        </div>

                        {{-- Baris 3: Parameter Logistik & Finansial --}}
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-md pt-1.5 border-t border-outline-variant/40">
                            <div class="space-y-1.5">
                                <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Harga Beli <span class="text-error font-normal">*</span></label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/70 font-bold text-xs">Rp</span>
                                    <input class="rupiah-input harga-input w-full bg-white border border-outline-variant rounded-xl p-2 pl-8 text-xs font-bold text-right focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none" name="harga_beli_terakhir[]" placeholder="0" required inputmode="numeric" type="text">
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Harga Jual Normal</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/70 font-bold text-xs">Rp</span>
                                    <input class="rupiah-input harga-jual-input w-full bg-white border border-outline-variant rounded-xl p-2 pl-8 text-xs font-bold text-right text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none" name="harga_jual_normal[]" placeholder="" inputmode="numeric" type="text">
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Stok Awal</label>
                                <input class="stok-awal-input w-full bg-white border border-outline-variant rounded-xl p-2 text-xs text-center font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none" name="stok_awal[]" min="0" step="any" value="0" type="number">
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Stok Aktif</label>
                                <input class="sisa-stok-input w-full bg-surface-container-low border border-outline-variant rounded-xl p-2 text-xs text-center font-bold text-primary cursor-not-allowed outline-none" readonly value="0" type="number" step="any" aria-label="Stok aktif saat ini">
                                <p class="text-[9px] leading-tight text-on-surface-variant">Berubah melalui transaksi/restock/adjustment, bukan edit master.</p>
                            </div>
                            <div class="space-y-1.5 col-span-2 md:col-span-1">
                                <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Limit Min Restock</label>
                                <input class="limit-input w-full bg-white border border-outline-variant rounded-xl p-2 text-xs text-center font-bold text-error focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none" name="limit_minimum_stok[]" min="0" step="any" value="0" type="number">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Form Action Footer Buttons --}}
            <div class="flex flex-col md:flex-row justify-between items-stretch md:items-center gap-md border-t border-outline-variant/60 pt-lg shrink-0">
                <button class="px-5 py-2.5 border border-dashed border-primary text-primary rounded-xl flex items-center justify-center gap-2 hover:bg-primary/5 transition-all text-xs font-bold cursor-pointer" id="addProductRowBtn" type="button" onclick="addProductRow()">
                    <span class="material-symbols-outlined text-lg">add_circle</span>
                    <span>Tambah Baris Produk Baru</span>
                </button>
                <div class="flex justify-end gap-3">
                    <button class="px-5 py-2.5 text-on-surface-variant text-xs font-bold hover:bg-surface-container-high rounded-xl transition-all cursor-pointer" onclick="closeProductModal()" type="button">Batal</button>
                    <button class="px-6 py-2.5 bg-primary text-on-primary text-xs font-bold rounded-xl shadow-md hover:brightness-110 transition-all active:scale-95 cursor-pointer" id="submitProductBtn" type="submit">Simpan Produk</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Summary Confirmation Modal - Stable Product Only --}}
<style id="tb39-product-summary-modal-style">
    /* FINAL FIX KHUSUS /product: modal confirm tidak ikut rule modal global agar tidak acak. */
    #productSummaryModal.tb-product-summary-modal {
        position: fixed !important;
        inset: 0 !important;
        z-index: 2147483000 !important;
        width: 100vw !important;
        height: 100vh !important;
        min-height: 100vh !important;
        padding: 16px !important;
        margin: 0 !important;
        display: none !important;
        align-items: center !important;
        justify-content: center !important;
        background: rgba(15, 23, 42, 0.58) !important;
        pointer-events: none !important;
        opacity: 1 !important;
        visibility: visible !important;
        overflow: hidden !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
        box-sizing: border-box !important;
    }

    #productSummaryModal.tb-product-summary-modal.tb-product-summary-open {
        display: flex !important;
        pointer-events: auto !important;
        opacity: 1 !important;
        visibility: visible !important;
    }

    #productSummaryModal .tb-product-summary-card {
        position: relative !important;
        z-index: 2147483001 !important;
        width: min(420px, calc(100vw - 32px)) !important;
        max-width: 420px !important;
        max-height: calc(100vh - 32px) !important;
        display: flex !important;
        flex-direction: column !important;
        overflow: hidden !important;
        border-radius: 18px !important;
        border: 1px solid #d7dee8 !important;
        background: #ffffff !important;
        color: #131b2e !important;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.24) !important;
        transform: none !important;
        animation: none !important;
        pointer-events: auto !important;
        box-sizing: border-box !important;
        font-family: Inter, Arial, sans-serif !important;
    }

    #productSummaryModal .tb-product-summary-header {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        padding: 18px 20px !important;
        border-bottom: 1px solid #e3e8ef !important;
        background: #f8fafc !important;
    }

    #productSummaryModal .tb-product-summary-icon {
        width: 42px !important;
        min-width: 42px !important;
        height: 42px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        border-radius: 14px !important;
        background: rgba(0, 108, 73, 0.10) !important;
        color: #006c49 !important;
    }

    #productSummaryModal .tb-product-summary-title {
        margin: 0 !important;
        font-size: 16px !important;
        line-height: 22px !important;
        font-weight: 800 !important;
        color: #131b2e !important;
    }

    #productSummaryModal .tb-product-summary-subtitle {
        margin: 2px 0 0 0 !important;
        font-size: 12px !important;
        line-height: 16px !important;
        color: #526173 !important;
    }

    #productSummaryModal .tb-product-summary-body {
        padding: 18px 20px !important;
        background: #ffffff !important;
    }

    #productSummaryModal #productSummaryText {
        margin: 0 !important;
        font-size: 13px !important;
        line-height: 20px !important;
        color: #3c4a42 !important;
    }

    #productSummaryModal .tb-product-summary-countbox {
        margin-top: 14px !important;
        padding: 14px !important;
        border-radius: 14px !important;
        border: 1px solid #d7dee8 !important;
        background: #f8fafc !important;
    }

    #productSummaryModal .tb-product-summary-label {
        margin: 0 !important;
        font-size: 10px !important;
        line-height: 14px !important;
        font-weight: 800 !important;
        letter-spacing: .06em !important;
        text-transform: uppercase !important;
        color: #64748b !important;
    }

    #productSummaryModal #productSummaryCount {
        margin: 3px 0 0 0 !important;
        font-size: 18px !important;
        line-height: 24px !important;
        font-weight: 900 !important;
        color: #006c49 !important;
    }

    #productSummaryModal .tb-product-summary-actions {
        display: flex !important;
        justify-content: flex-end !important;
        align-items: center !important;
        gap: 10px !important;
        padding: 14px 20px !important;
        border-top: 1px solid #e3e8ef !important;
        background: #f8fafc !important;
    }

    #productSummaryModal .tb-product-summary-btn {
        min-height: 40px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        border-radius: 10px !important;
        padding: 0 16px !important;
        font-size: 12px !important;
        line-height: 1 !important;
        font-weight: 800 !important;
        border: 1px solid transparent !important;
        cursor: pointer !important;
        pointer-events: auto !important;
        white-space: nowrap !important;
        opacity: 1 !important;
        visibility: visible !important;
    }

    #productSummaryModal .tb-product-summary-btn-cancel {
        color: #334155 !important;
        background: #ffffff !important;
        border-color: #cbd5e1 !important;
    }

    #productSummaryModal .tb-product-summary-btn-submit {
        color: #ffffff !important;
        background: #006c49 !important;
        border-color: #006c49 !important;
        box-shadow: 0 8px 18px rgba(0, 108, 73, 0.18) !important;
    }

    #productSummaryModal .tb-product-summary-btn:hover {
        filter: brightness(0.97) !important;
    }

    #productSummaryModal .tb-product-summary-btn:active {
        transform: scale(0.98) !important;
    }

    @media (max-width: 480px) {
        #productSummaryModal.tb-product-summary-modal {
            align-items: flex-end !important;
            padding: 10px !important;
        }
        #productSummaryModal .tb-product-summary-card {
            width: 100% !important;
            max-width: none !important;
            border-radius: 16px !important;
        }
        #productSummaryModal .tb-product-summary-actions {
            flex-direction: column-reverse !important;
            align-items: stretch !important;
        }
        #productSummaryModal .tb-product-summary-btn {
            width: 100% !important;
        }
    }
</style>

<div id="productSummaryModal" class="tb-product-summary-modal hidden" style="display:none;" aria-hidden="true">
    <div class="tb-product-summary-card" role="dialog" aria-modal="true" aria-labelledby="productSummaryTitle" aria-describedby="productSummaryText">
        <div class="tb-product-summary-header">
            <div class="tb-product-summary-icon">
                <span class="material-symbols-outlined text-xl">rule</span>
            </div>
            <div>
                <h4 class="tb-product-summary-title" id="productSummaryTitle">Konfirmasi Eksekusi</h4>
                <p class="tb-product-summary-subtitle">Validasi final data sebelum masuk database.</p>
            </div>
        </div>

        <div class="tb-product-summary-body">
            <p id="productSummaryText">Anda akan menyimpan data produk ke database. Apakah seluruh data sudah benar?</p>
            <div class="tb-product-summary-countbox">
                <p class="tb-product-summary-label">Total Item Records</p>
                <p id="productSummaryCount">0 item produk</p>
            </div>
        </div>

        <div class="tb-product-summary-actions">
            <button class="tb-product-summary-btn tb-product-summary-btn-cancel" type="button" onclick="hideProductSummaryModal()">Periksa Lagi</button>
            <button class="tb-product-summary-btn tb-product-summary-btn-submit" type="button" onclick="executeProductSubmit()">Ya, Eksekusi</button>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const productRoutes = {
            store: @json(route('product.store')),
            showBase: @json(url('/product'))
        };

        const productModal = document.getElementById('productModal');
        const productForm = document.getElementById('productForm');
        const productRows = document.getElementById('productRows');
        const methodContainer = document.getElementById('methodContainer');
        const addProductRowBtn = document.getElementById('addProductRowBtn');
        const submitProductBtn = document.getElementById('submitProductBtn');
        const productModalTitle = document.getElementById('productModalTitle');
        const productModalSubtitle = document.getElementById('productModalSubtitle');
        const productModalIcon = document.getElementById('productModalIcon');
        const productSummaryModal = document.getElementById('productSummaryModal');
        const productSummaryText = document.getElementById('productSummaryText');
        const productSummaryCount = document.getElementById('productSummaryCount');
        const excelFileInput = document.getElementById('excelFileInput');
        const excelImportForm = document.getElementById('excelImportForm');

        if (excelFileInput && excelImportForm) {
            excelFileInput.addEventListener('change', function () {
                if (this.files && this.files.length > 0) {
                    const labelText = this.parentElement.querySelector('span:not(.material-symbols-outlined)');
                    if (labelText) labelText.textContent = 'Memproses...';
                    excelImportForm.submit();
                }
            });
        }

        let productSubmitConfirmed = false;
        let currentProductMode = 'create';

        window.openCreateProductModal = openCreateProductModal;
        window.openShowProductModal = openShowProductModal;
        window.openEditProductModal = openEditProductModal;
        window.closeProductModal = closeProductModal;
        window.addProductRow = addProductRow;
        window.removeProductRow = removeProductRow;
        window.executeProductSubmit = executeProductSubmit;
        window.hideProductSummaryModal = hideProductSummaryModal;
        window.confirmDeleteProduct = confirmDeleteProduct;

        function openProductModal() {
            if (!productModal) return;
            productModal.classList.remove('hidden');
            productModal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeProductModal() {
            if (!productModal) return;
            productModal.classList.add('hidden');
            productModal.classList.remove('flex');
            document.body.style.overflow = '';
        }

        function resetProductForm() {
            if (!productForm || !productRows) return;
            productSubmitConfirmed = false;
            productForm.reset();
            productForm.action = productRoutes.store;
            productForm.method = 'POST';
            methodContainer.innerHTML = '';
            
            const rows = productRows.querySelectorAll('.product-row');
            for (let i = 1; i < rows.length; i++) {
                rows[i].remove();
            }
            
            const firstRow = productRows.querySelector('.product-row');
            if (firstRow) {
                firstRow.dataset.rowIndex = '0';
                const title = firstRow.querySelector('.row-title');
                const removeBtn = firstRow.querySelector('.remove-row-btn');
                
                if (title) title.textContent = 'Produk #1';
                if (removeBtn) removeBtn.classList.add('hidden');
                
                prepareRowForCreate(firstRow, true);
                updateRowNumbers();
            }
        }

        function openCreateProductModal() {
            currentProductMode = 'create';
            resetProductForm();
            if (productModalTitle) productModalTitle.textContent = 'Tambah Produk Baru';
            if (productModalSubtitle) productModalSubtitle.textContent = 'Input satu atau banyak produk material bangunan.';
            if (productModalIcon) productModalIcon.textContent = 'add_box';
            if (addProductRowBtn) addProductRowBtn.classList.remove('hidden');
            if (submitProductBtn) {
                submitProductBtn.classList.remove('hidden');
                submitProductBtn.textContent = 'Simpan Produk';
            }
            setRowsReadOnly(false);
            openProductModal();
        }

        async function openShowProductModal(id) {
            currentProductMode = 'show';
            resetProductForm();
            const data = await fetchProduct(id);
            if (!data) return;

            if (productModalTitle) productModalTitle.textContent = 'Lihat Detail Produk';
            if (productModalSubtitle) productModalSubtitle.textContent = 'Seluruh field dikunci dalam mode preview.';
            if (productModalIcon) productModalIcon.textContent = 'visibility';
            if (addProductRowBtn) addProductRowBtn.classList.add('hidden');
            if (submitProductBtn) submitProductBtn.classList.add('hidden');
            
            fillSingleProductRow(data, 'show');
            setRowsReadOnly(true);
            openProductModal();
        }

        async function openEditProductModal(id) {
            currentProductMode = 'edit';
            resetProductForm();
            const data = await fetchProduct(id);
            if (!data) return;

            if (productModalTitle) productModalTitle.textContent = 'Edit Data Produk';
            if (productModalSubtitle) productModalSubtitle.textContent = 'Perbarui satu produk terpilih dari database MySQL.';
            if (productModalIcon) productModalIcon.textContent = 'edit';
            
            productForm.action = productRoutes.showBase + '/' + id;
            methodContainer.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            
            if (addProductRowBtn) addProductRowBtn.classList.add('hidden');
            if (submitProductBtn) {
                submitProductBtn.classList.remove('hidden');
                submitProductBtn.textContent = 'Update Produk';
            }
            
            fillSingleProductRow(data, 'edit');
            setRowsReadOnly(false);
            openProductModal();
        }

        async function fetchProduct(id) {
            try {
                const response = await fetch(productRoutes.showBase + '/' + id, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) throw new Error('Produk gagal dimuat');
                return await response.json();
            } catch (error) {
                alert('Data produk tidak ditemukan atau gagal dimuat.');
                console.error(error);
                return null;
            }
        }

        function fillSingleProductRow(data, mode) {
            const row = productRows.querySelector('.product-row');
            if (!row) return;

            const kodeInput = row.querySelector('.kode-barang-input');
            const namaInput = row.querySelector('.nama-barang-input');
            const deskripsiInput = row.querySelector('.deskripsi-input');
            const satuanSelect = row.querySelector('.satuan-select');
            const satuanCustomInput = row.querySelector('.satuan-custom-input');
            const hargaInput = row.querySelector('.harga-input');
            const hargaJualInput = row.querySelector('.harga-jual-input');
            const stokAwalInput = row.querySelector('.stok-awal-input');
            const sisaStokInput = row.querySelector('.sisa-stok-input');
            const limitInput = row.querySelector('.limit-input');
            const kodeModes = row.querySelectorAll('.kode-mode-radio');

            const rowTitle = row.querySelector('.row-title');
            if (rowTitle) rowTitle.textContent = mode === 'edit' ? 'Produk Terpilih' : 'Detail Produk';
            
            kodeModes.forEach(function (radio) {
                radio.checked = radio.value === 'manual';
            });
            updateKodeModeState(row);

            if (kodeInput) {
                kodeInput.value = data.kode_barang || '';
                kodeInput.readOnly = (mode === 'show');
                kodeInput.classList.remove('bg-surface-container-low', 'text-on-surface-variant', 'cursor-not-allowed');
                kodeInput.classList.add('bg-white', 'text-on-surface');
            }
            if (namaInput) namaInput.value = data.nama_barang || '';
            if (deskripsiInput) deskripsiInput.value = data.deskripsi || '';
            if (hargaInput) hargaInput.value = formatRupiah(String(data.harga_beli_terakhir || 0));
            if (hargaJualInput) hargaJualInput.value = formatRupiah(String(data.harga_jual_normal || 0));
            if (stokAwalInput) stokAwalInput.value = data.stok_awal || 0;
            if (sisaStokInput) sisaStokInput.value = data.sisa_stok || 0;
            if (limitInput) limitInput.value = data.limit_minimum_stok || 0;

            if (satuanSelect && satuanCustomInput) {
                const defaultOptions = Array.from(satuanSelect.options).map(o => o.value);
                if (defaultOptions.includes(data.satuan)) {
                    satuanSelect.value = data.satuan;
                    satuanSelect.name = mode === 'edit' ? 'satuan_single' : 'satuan[]';
                    satuanSelect.classList.remove('hidden');
                    satuanCustomInput.classList.add('hidden');
                    satuanCustomInput.removeAttribute('name');
                    satuanCustomInput.required = false;
                } else {
                    satuanSelect.value = '__custom__';
                    satuanSelect.classList.add('hidden');
                    satuanSelect.removeAttribute('name');
                    satuanCustomInput.classList.remove('hidden');
                    satuanCustomInput.name = mode === 'edit' ? 'satuan_single' : 'satuan[]';
                    satuanCustomInput.required = true;
                    satuanCustomInput.value = data.satuan || '';
                }
            }

            if (mode === 'edit') {
                if (kodeInput) kodeInput.name = 'kode_barang_single';
                if (namaInput) namaInput.name = 'nama_barang_single';
                if (deskripsiInput) deskripsiInput.name = 'deskripsi_single';
                if (hargaInput) hargaInput.name = 'harga_beli_terakhir_single';
                if (hargaJualInput) hargaJualInput.name = 'harga_jual_normal_single';
                if (stokAwalInput) stokAwalInput.name = 'stok_awal_single';
                if (sisaStokInput) sisaStokInput.removeAttribute('name');
                if (limitInput) limitInput.name = 'limit_minimum_stok_single';
                kodeModes.forEach(radio => radio.disabled = true);
            }
        }

        function prepareRowForCreate(row, keepAutoValue) {
            const index = row.dataset.rowIndex || '0';
            row.querySelectorAll('input, textarea, select').forEach(function (field) {
                field.disabled = false;
                field.readOnly = false;
            });

            row.querySelectorAll('.kode-mode-radio').forEach(function (radio) {
                radio.name = `kode_mode[${index}]`;
                radio.disabled = false;
                radio.checked = radio.value === 'otomatis';
            });

            const kodeInput = row.querySelector('.kode-barang-input');
            if (kodeInput) {
                kodeInput.name = 'kode_barang[]';
                kodeInput.value = keepAutoValue ? (kodeInput.dataset.autoValue || '') : '';
                kodeInput.readOnly = true;
                kodeInput.classList.add('bg-surface-container-low', 'text-on-surface-variant', 'cursor-not-allowed');
                kodeInput.classList.remove('bg-white', 'text-on-surface');
            }

            if (row.querySelector('.nama-barang-input')) row.querySelector('.nama-barang-input').name = 'nama_barang[]';
            if (row.querySelector('.deskripsi-input')) row.querySelector('.deskripsi-input').name = 'deskripsi[]';
            
            const satuanSelect = row.querySelector('.satuan-select');
            const satuanCustom = row.querySelector('.satuan-custom-input');
            if (satuanSelect && satuanCustom) {
                satuanSelect.name = 'satuan[]';
                satuanSelect.value = 'Zak';
                satuanSelect.classList.remove('hidden');
                satuanCustom.value = '';
                satuanCustom.classList.add('hidden');
                satuanCustom.removeAttribute('name');
                satuanCustom.required = false;
            }

            if (row.querySelector('.harga-input')) row.querySelector('.harga-input').name = 'harga_beli_terakhir[]';
            if (row.querySelector('.harga-jual-input')) row.querySelector('.harga-jual-input').name = 'harga_jual_normal[]';
            if (row.querySelector('.stok-awal-input')) row.querySelector('.stok-awal-input').name = 'stok_awal[]';
            if (row.querySelector('.sisa-stok-input')) row.querySelector('.sisa-stok-input').removeAttribute('name');
            if (row.querySelector('.limit-input')) row.querySelector('.limit-input').name = 'limit_minimum_stok[]';
            
            updateKodeModeState(row);
        }

        function initializeProductRow(row) {
            row.querySelectorAll('.kode-mode-radio').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    updateKodeModeState(row);
                });
            });

            const satuanSelect = row.querySelector('.satuan-select');
            const satuanCustomInput = row.querySelector('.satuan-custom-input');
            if (satuanSelect && satuanCustomInput) {
                satuanSelect.addEventListener('change', function () {
                    if (satuanSelect.value === '__custom__') {
                        satuanSelect.classList.add('hidden');
                        satuanSelect.removeAttribute('name');
                        satuanCustomInput.classList.remove('hidden');
                        satuanCustomInput.name = currentProductMode === 'edit' ? 'satuan_single' : 'satuan[]';
                        satuanCustomInput.required = true;
                        satuanCustomInput.focus();
                    }
                });

                satuanCustomInput.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        satuanCustomInput.value = '';
                        satuanCustomInput.classList.add('hidden');
                        satuanCustomInput.removeAttribute('name');
                        satuanCustomInput.required = false;
                        satuanSelect.name = currentProductMode === 'edit' ? 'satuan_single' : 'satuan[]';
                        satuanSelect.value = 'Zak';
                        satuanSelect.classList.remove('hidden');
                        satuanSelect.focus();
                    }
                });
            }

            row.querySelectorAll('.harga-input, .harga-jual-input').forEach(function (rupiahInput) {
                rupiahInput.addEventListener('input', function () {
                    rupiahInput.value = formatRupiah(rupiahInput.value);
                });
            });

            const stokAwalInput = row.querySelector('.stok-awal-input');
            const sisaStokInput = row.querySelector('.sisa-stok-input');
            if (stokAwalInput && sisaStokInput) {
                stokAwalInput.addEventListener('input', function () {
                    if (currentProductMode === 'create') {
                        sisaStokInput.value = stokAwalInput.value === '' ? 0 : stokAwalInput.value;
                    }
                });
            }
        }

        function updateKodeModeState(row) {
            const selected = row.querySelector('.kode-mode-radio:checked');
            const kodeInput = row.querySelector('.kode-barang-input');
            const labels = row.querySelectorAll('.kode-mode-label');
            
            labels.forEach(function (label) {
                const radio = label.querySelector('.kode-mode-radio');
                if (radio && radio.checked) {
                    label.classList.add('bg-primary', 'text-on-primary');
                    label.classList.remove('text-on-surface-variant');
                } else {
                    label.classList.remove('bg-primary', 'text-on-primary');
                    label.classList.add('text-on-surface-variant');
                }
            });

            if (!kodeInput) return;

            if (!selected || selected.value === 'otomatis') {
                kodeInput.readOnly = true;
                kodeInput.value = kodeInput.dataset.autoValue || '';
                kodeInput.classList.add('bg-surface-container-low', 'text-on-surface-variant', 'cursor-not-allowed');
                kodeInput.classList.remove('bg-white', 'text-on-surface');
            } else {
                kodeInput.readOnly = false;
                if (kodeInput.value === kodeInput.dataset.autoValue) {
                    kodeInput.value = '';
                }
                kodeInput.classList.remove('bg-surface-container-low', 'text-on-surface-variant', 'cursor-not-allowed');
                kodeInput.classList.add('bg-white', 'text-on-surface');
                if (document.activeElement !== kodeInput) kodeInput.focus();
            }
        }

        function addProductRow() {
            const sourceRow = productRows.querySelector('.product-row');
            if (!sourceRow) return;

            const clone = sourceRow.cloneNode(true);
            const currentLength = productRows.querySelectorAll('.product-row').length;
            clone.dataset.rowIndex = String(currentLength);
            
            const removeBtn = clone.querySelector('.remove-row-btn');
            if (removeBtn) removeBtn.classList.remove('hidden');

            productRows.appendChild(clone);
            prepareRowForCreate(clone, false);
            initializeProductRow(clone);
            updateRowNumbers();
            clone.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function removeProductRow(button) {
            const rows = productRows.querySelectorAll('.product-row');
            if (rows.length <= 1) return;
            button.closest('.product-row').remove();
            updateRowNumbers();
        }

        function updateRowNumbers() {
            const rows = productRows.querySelectorAll('.product-row');
            rows.forEach(function (row, index) {
                row.dataset.rowIndex = String(index);
                row.querySelectorAll('.kode-mode-radio').forEach(function(radio) {
                    radio.name = `kode_mode[${index}]`;
                });

                const rowTitle = row.querySelector('.row-title');
                if (rowTitle && currentProductMode === 'create') {
                    rowTitle.textContent = 'Produk #' + (index + 1);
                }

                const removeBtn = row.querySelector('.remove-row-btn');
                if (removeBtn) {
                    if (currentProductMode === 'create' && rows.length > 1) {
                        removeBtn.classList.remove('hidden');
                    } else {
                        removeBtn.classList.add('hidden');
                    }
                }
            });
        }

        function setRowsReadOnly(readOnly) {
            productRows.querySelectorAll('input, textarea, select').forEach(function (field) {
                if (field.type === 'hidden') return;
                
                if (readOnly) {
                    field.readOnly = true;
                    field.disabled = field.tagName.toLowerCase() === 'select' || field.type === 'radio';
                    field.classList.add('bg-surface-container-low', 'cursor-not-allowed');
                } else {
                    if (field.type === 'radio' && currentProductMode === 'edit') {
                        field.disabled = true;
                    } else {
                        field.disabled = false;
                    }

                    if (!field.classList.contains('sisa-stok-input')) {
                        field.readOnly = false;
                    }
                    field.classList.remove('cursor-not-allowed');
                }
            });

            productRows.querySelectorAll('.sisa-stok-input').forEach(function (field) {
                field.readOnly = true;
                field.classList.add('bg-surface-container-low', 'cursor-not-allowed');
            });

            if (!readOnly) {
                productRows.querySelectorAll('.product-row').forEach(function (row) {
                    updateKodeModeState(row);
                });
            }
        }

        function formatRupiah(value) {
            const digits = String(value).replace(/[^0-9]/g, '');
            if (digits === '') return '';
            return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function normalizeCurrencyInputs() {
            productForm.querySelectorAll('.harga-input, .harga-jual-input').forEach(function (input) {
                input.value = String(input.value).replace(/[^0-9]/g, '');
            });
        }

        if (productForm) {
            productForm.addEventListener('submit', function (event) {
                if (productSubmitConfirmed) {
                    normalizeCurrencyInputs();
                    return;
                }
                event.preventDefault();
                if (currentProductMode === 'show') return;
                if (!productForm.reportValidity()) return;

                const totalRows = currentProductMode === 'create' ? productRows.querySelectorAll('.product-row').length : 1;
                if (currentProductMode === 'create') {
                    if (productSummaryText) productSummaryText.textContent = 'Anda akan menyimpan sebanyak ' + totalRows + ' item produk baru ke database MySQL. Apakah seluruh data sudah benar?';
                    if (productSummaryCount) productSummaryCount.textContent = totalRows + ' item produk';
                } else {
                    if (productSummaryText) productSummaryText.textContent = 'Anda akan memperbarui data produk terpilih di database MySQL. Apakah seluruh data sudah benar?';
                    if (productSummaryCount) productSummaryCount.textContent = '1 item produk';
                }
                showProductSummaryModal();
            });
        }
        function showProductSummaryModal() {
            if (!productSummaryModal) return;

            productSummaryModal.classList.remove('hidden');
            productSummaryModal.classList.add('tb-product-summary-open');
            productSummaryModal.style.setProperty('display', 'flex', 'important');
            productSummaryModal.style.setProperty('pointer-events', 'auto', 'important');
            productSummaryModal.style.setProperty('visibility', 'visible', 'important');
            productSummaryModal.style.setProperty('opacity', '1', 'important');
            productSummaryModal.setAttribute('aria-hidden', 'false');

            const card = productSummaryModal.querySelector('.tb-product-summary-card');
            if (card) {
                card.style.setProperty('pointer-events', 'auto', 'important');
                card.style.setProperty('visibility', 'visible', 'important');
                card.style.setProperty('opacity', '1', 'important');
            }

            document.body.classList.add('tb-has-visible-modal');

            if (window.TB39ModalSystem && typeof window.TB39ModalSystem.refresh === 'function') {
                window.setTimeout(function () { window.TB39ModalSystem.refresh(); }, 30);
            }

            window.setTimeout(function () {
                const firstButton = productSummaryModal.querySelector('button');
                if (firstButton) firstButton.focus({ preventScroll: true });
            }, 40);
        }

        function hideProductSummaryModal() {
            if (!productSummaryModal) return;

            productSummaryModal.classList.add('hidden');
            productSummaryModal.classList.remove('tb-product-summary-open');
            productSummaryModal.style.setProperty('display', 'none', 'important');
            productSummaryModal.style.setProperty('pointer-events', 'none', 'important');
            productSummaryModal.setAttribute('aria-hidden', 'true');

            if (window.TB39ModalSystem && typeof window.TB39ModalSystem.refresh === 'function') {
                window.setTimeout(function () { window.TB39ModalSystem.refresh(); }, 30);
            }
        }

        function executeProductSubmit() {
            productSubmitConfirmed = true;
            hideProductSummaryModal();
            normalizeCurrencyInputs();

            const submitButton = productSummaryModal ? productSummaryModal.querySelector('.tb-product-summary-btn-submit') : null;
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.style.opacity = '0.75';
            }

            if (productForm) {
                HTMLFormElement.prototype.submit.call(productForm);
            }
        }

        function confirmDeleteProduct(event, productName) {
            const firstConfirm = confirm('Hapus produk "' + productName + '" dari database MySQL?');
            if (!firstConfirm) {
                event.preventDefault();
                return false;
            }
            const secondConfirm = confirm('Konfirmasi terakhir: data produk dan stok_barang terkait akan dihapus. Lanjutkan?');
            if (!secondConfirm) {
                event.preventDefault();
                return false;
            }
            return true;
        }

        productRows.querySelectorAll('.product-row').forEach(function (row) {
            initializeProductRow(row);
            updateKodeModeState(row);
        });

        const createProductButton = document.getElementById('openProductCreateBtn') || document.querySelector('[data-open-product-modal="true"]');
        if (createProductButton) {
            createProductButton.addEventListener('click', function (event) {
                event.preventDefault();
                openCreateProductModal();
            });
        }
    });
</script>
@endpush