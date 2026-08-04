@extends('layouts.app')

@section('content')
<style>
.material-symbols-outlined {
    font-variation-settings: 'FILL'0, 'wght'400, 'GRAD'0, 'opsz'24;
}

.glass-modal {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}

.pricing-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.pricing-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.pricing-scrollbar::-webkit-scrollbar-thumb {
    background: #bbcabf;
    border-radius: 10px;
}
</style>

<div class="min-h-full bg-surface text-on-surface font-body-md selection:bg-primary-container selection:text-on-primary-container">
    <div class="max-w-7xl mx-auto space-y-lg">
        <div class="flex flex-col lg:flex-row lg:items-end justify-between mb-8 gap-6">
            <div>
                <h2 class="font-headline-xl text-headline-xl text-on-surface uppercase text-primary" id="pageTitle">
                    Manajemen Harga Jual</h2>
                <p class="text-body-md text-on-surface-variant mt-2">Kelola harga normal retail dan harga khusus untuk
                    toko atau sales.</p>
            </div>

        </div>
        <div class="flex flex-wrap items-center gap-2">
        
        <button class="inline-flex items-center justify-center gap-1.5 bg-primary hover:bg-opacity-95 text-on-primary px-4 rounded-lg shadow-sm transition-all duration-200 active:scale-95 font-semibold text-sm h-10" id="mainActionButton" type="button" onclick="openPricingModal()">
            <span class="material-symbols-outlined text-[18px] leading-none">settings</span>
            <span id="buttonText" class="leading-none">Pilih Kategori Harga</span>
        </button>

        <button class="tb-pricing-action-btn tb-pricing-template-btn inline-flex items-center justify-center gap-1.5 px-4 rounded-lg shadow-sm transition-all duration-200 active:scale-95 font-semibold text-sm h-10" disabled id="downloadTemplateButton" type="button" onclick="downloadImportTemplate()">
            <span class="material-symbols-outlined text-[18px] leading-none">download</span>
            <span class="leading-none">Download Template</span>
        </button>

        <button class="tb-pricing-action-btn tb-pricing-import-btn inline-flex items-center justify-center gap-1.5 px-4 rounded-lg shadow-sm transition-all duration-200 active:scale-95 font-semibold text-sm h-10" disabled id="importPriceButton" type="button" onclick="openImportPriceModal()">
            <span class="material-symbols-outlined text-[18px] leading-none">upload_file</span>
            <span class="leading-none">Import Harga Khusus</span>
        </button>
        
    </div>


        <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant shadow-sm mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 items-end">
                <div class="space-y-2">
                    <label class="block font-label-md text-on-surface-variant">Kategori</label>
                    <div class="relative">
                        <select
                            class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-4 py-2.5 appearance-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none"
                            id="categorySelect" onchange="handleCategoryChange(this.value)">
                            <option value="all" selected>Semua Data Harga (All)</option>
                            <option value="normal">User / Normal</option>
                            <option value="toko">Toko Spesifik</option>
                            <option value="sales">Sales Spesifik</option>
                        </select>
                        <span
                            class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-on-surface-variant">expand_more</span>
                    </div>
                </div>
                <div class="space-y-2 opacity-50 pointer-events-none" id="targetContainer">
                    <label class="block font-label-md text-on-surface-variant" id="targetLabel">Target</label>
                    <div class="relative">
                        <select
                            class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-4 py-2.5 appearance-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none"
                            disabled id="targetSelect">
                            <option value="">Pilih Target...</option>
                        </select>
                        <span
                            class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-on-surface-variant">expand_more</span>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block font-label-md text-on-surface-variant">Cari Produk</label>
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined absolute left-3 text-on-surface-variant">search</span>
                        <input
                            class="w-full bg-surface-container-low border border-outline-variant rounded-xl pl-10 pr-4 py-2.5 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none"
                            id="tableSearch" placeholder="Kode atau Nama Barang" type="text" />
                    </div>
                </div>
                <div class="flex gap-2">
                    <button
                        class="flex-1 bg-surface-container-high text-on-surface px-4 py-2.5 rounded-xl border border-outline-variant font-bold hover:bg-surface-container-highest transition-colors"
                        onclick="resetFilters()" type="button">Reset</button>
                    <button
                        class="flex-1 bg-secondary text-on-secondary px-4 py-2.5 rounded-xl font-bold hover:bg-opacity-90 transition-colors shadow-sm"
                        onclick="applyFilters()" type="button">Terapkan</button>
                </div>
            </div>
        </div>

        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant overflow-hidden shadow-sm">
            <div class="overflow-x-auto pricing-scrollbar">
                <table class="w-full text-left border-collapse" id="pricingTable">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant" id="tableHeader"></tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant" id="tableBody"></tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-outline-variant flex flex-col sm:flex-row items-center justify-between gap-4 bg-surface-container-low/50">
                <span class="text-xs font-semibold text-on-surface-variant" id="mainPaginationSummary">
                    Menampilkan 0 data
                </span>
                <div class="flex items-center gap-2">
                    <button type="button" id="mainPrevPageBtn" onclick="changeMainPage(-1)" class="h-9 px-4 rounded-xl border border-outline-variant bg-white text-xs font-bold text-on-surface hover:bg-surface-container-high transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                        Sebelumnya
                    </button>
                    <span class="text-xs font-bold text-on-surface px-2" id="mainPageInfo">
                        Halaman 1 dari 1
                    </span>
                    <button type="button" id="mainNextPageBtn" onclick="changeMainPage(1)" class="h-9 px-4 rounded-xl border border-outline-variant bg-white text-xs font-bold text-on-surface hover:bg-surface-container-high transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                        Berikutnya
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="fixed inset-0 z-50 flex items-center justify-center p-4 opacity-0 pointer-events-none transition-all duration-300"
    id="pricingModal">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-md" onclick="closePricingModal()"></div>
    <div class="glass-modal w-full max-w-5xl rounded-2xl border border-white/40 shadow-2xl relative overflow-hidden flex flex-col max-h-[90vh] translate-y-4 transition-transform duration-300"
        id="pricingModalPanel">
        <div class="p-6 border-b border-outline-variant/30 bg-white/50">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-headline-md text-headline-md text-on-surface" id="modalTitle">Atur Harga Jual</h3>
                    <p class="text-label-md text-on-surface-variant opacity-70" id="modalSubtitle">Daftar seluruh barang
                        akan dimuat dari database.</p>
                </div>
                <button class="p-2 hover:bg-surface-container-high rounded-full transition-colors"
                    onclick="closePricingModal()" type="button">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>

        <div class="px-6 py-4 bg-white/70 border-b border-outline-variant/30">
            <div class="grid grid-cols-1 lg:grid-cols-[1fr_auto] gap-4 items-end">
                <div class="space-y-2">
                    <label class="block font-label-md text-on-surface-variant uppercase tracking-wider">Cari Produk di
                        Modal</label>
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                        <input
                            class="w-full bg-white border border-outline-variant rounded-xl pl-10 pr-4 py-3 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none"
                            id="modalProductSearch" placeholder="Cari nama barang atau kode barang..." type="text" />
                    </div>
                </div>
                <div class="flex items-center justify-between lg:justify-end gap-3">
                    <button
                        class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-low text-on-surface font-bold hover:bg-surface-container-high transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                        id="modalPrevPageBtn" type="button">Sebelumnya</button>
                    <span class="text-label-md text-on-surface-variant min-w-[120px] text-center"
                        id="modalPaginationInfo">Halaman 1 / 1</span>
                    <button
                        class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface-container-low text-on-surface font-bold hover:bg-surface-container-high transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                        id="modalNextPageBtn" type="button">Berikutnya</button>
                </div>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto bg-surface-container-lowest pricing-scrollbar">
            <form id="formBulkPricing" method="POST" action="{{ route('pricing.store') }}">
                @csrf
                <input type="hidden" name="category" id="formCategory" value="normal" />
                <input type="hidden" name="target_id" id="formTargetId" value="" />
                <table class="w-full text-left border-collapse">
                    <thead class="sticky top-0 bg-surface-container-low z-10 border-b border-outline-variant">
                        <tr>
                            <th class="px-6 py-4 font-label-md text-on-surface-variant uppercase">Produk</th>
                            <th class="px-4 py-4 font-label-md text-on-surface-variant uppercase text-right"
                                id="modalRefColLabel">Harga Acuan</th>
                            <th class="px-4 py-4 font-label-md text-on-surface-variant uppercase text-right"
                                id="modalActiveColLabel">Harga Baru</th>
                            <th class="px-4 py-4 font-label-md text-on-surface-variant uppercase text-center w-24">Aksi
                                Edit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/50" id="modalTableBody"></tbody>
                </table>
            </form>
        </div>
        <div
            class="p-6 bg-surface-container-low border-t border-outline-variant/30 flex justify-between items-center gap-3">
            <p class="text-label-md text-on-surface-variant" id="modalRowInfo">0 produk dimuat.</p>
            <div class="flex justify-end gap-3">
                <button
                    class="px-6 py-2.5 rounded-xl border border-outline-variant font-bold hover:bg-white transition-all"
                    onclick="closePricingModal()" type="button">Batal</button>
                <button
                    class="px-8 py-2.5 rounded-xl bg-primary text-on-primary font-bold shadow-lg shadow-primary/30 hover:bg-opacity-90 transition-all flex items-center gap-2"
                    id="submitFormBtn" type="button">
                    <span class="material-symbols-outlined text-[18px]">save</span>
                    Simpan Perubahan
                </button>
            </div>
        </div>
    </div>
</div>


<div class="fixed inset-0 z-[65] hidden items-center justify-center p-4 bg-black/50 backdrop-blur-md transition-all animate-fade-in" id="importPriceModal">
    <div class="absolute inset-0 bg-transparent" onclick="closeImportPriceModal()"></div>
    
    <div class="relative w-full max-w-4xl bg-white rounded-2xl border border-outline-variant/40 shadow-2xl overflow-hidden max-h-[88vh] flex flex-col transition-all duration-200">
        
        <div class="px-6 py-4 border-b border-outline-variant/30 bg-surface-container-low flex items-center justify-between gap-4">
            <div>
                <h4 class="text-lg font-black text-primary tracking-tight uppercase flex items-center gap-2">
                    <span class="material-symbols-outlined text-[22px]">upload_file</span>
                    Import Harga Khusus
                </h4>
                <p class="text-xs text-on-surface-variant mt-0.5 font-medium opacity-90" id="importTargetInfo">
                    Pilih kategori toko atau sales dan target terlebih dahulu.
                </p>
            </div>
            <button class="w-8 h-8 inline-flex items-center justify-center rounded-full text-on-surface-variant hover:bg-surface-container-high transition-colors active:scale-95" onclick="closeImportPriceModal()" type="button">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        <div class="px-6 py-5 border-b border-outline-variant/30 bg-white">
            <div class="grid grid-cols-1 lg:grid-cols-[1fr_auto] gap-4 items-end">
                
                <form class="space-y-2 flex-1" enctype="multipart/form-data" id="formImportPreview">
                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">
                        Upload File Template CSV
                    </label>
                    
                    <div class="flex items-center gap-2">
                        <label for="importFileInput" class="inline-flex items-center justify-center gap-1.5 bg-surface-container-high hover:bg-opacity-90 text-on-surface px-4 rounded-lg border border-outline-variant transition-all font-semibold text-xs h-10 cursor-pointer select-none active:scale-95 shrink-0">
                            <span class="material-symbols-outlined text-[18px]">folder_open</span>
                            Pilih File
                        </label>
                        
                        <input type="text" id="fileNameViewer" placeholder="Belum ada file terpilih..." readonly class="w-full h-10 px-3 text-xs bg-surface-container-low rounded-lg border border-outline-variant/60 text-on-surface focus:outline-none font-medium truncate" />
                    </div>

                    <input accept=".csv,text/csv,text/plain" class="hidden" id="importFileInput" name="template_file" type="file" required onchange="document.getElementById('fileNameViewer').value = this.files[0] ? this.files[0].name : ''" />
                </form>
                
                <div class="flex flex-col gap-2 w-full lg:w-auto">
    <button class="tb-pricing-template-btn inline-flex items-center justify-center gap-1.5 px-4 rounded-lg text-xs font-semibold h-10 transition-all active:scale-95 w-full" type="button" onclick="downloadImportTemplate()">
        <span class="material-symbols-outlined text-[18px]">download</span>
        Download Template
    </button>
    
    <button class="tb-pricing-import-btn inline-flex items-center justify-center gap-1.5 px-4 rounded-lg text-xs font-semibold h-10 transition-all active:scale-95 shadow-sm w-full" form="formImportPreview" type="submit">
        <span class="material-symbols-outlined text-[18px]">visibility</span>
        Preview Import
    </button>
</div>

            </div>
        </div>

        <div class="flex-1 overflow-y-auto pricing-scrollbar bg-surface-container-lowest">
            <div class="p-6 space-y-4">
                
                <div class="hidden rounded-xl border border-outline-variant/40 bg-surface-container-low p-4 space-y-1" id="importSummaryBox">
                    <p class="text-xs font-bold text-on-surface flex items-center gap-1.5" id="importSummaryText">
                        <span class="material-symbols-outlined text-[16px] text-primary">analytics</span>
                        Belum ada preview.
                    </p>
                    <p class="text-[11px] font-medium text-on-surface-variant" id="importMessageText"></p>
                </div>

                <div class="overflow-x-auto pricing-scrollbar border border-outline-variant/30 rounded-xl bg-white shadow-sm">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-surface-container-high sticky top-0 z-10 shadow-sm">
                            <tr class="border-b border-outline-variant/30">
                                <th class="px-4 py-2.5 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider w-14">Baris</th>
                                <th class="px-4 py-2.5 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider w-24">Status</th>
                                <th class="px-4 py-2.5 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Produk</th>
                                <th class="px-4 py-2.5 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider text-right w-32">Harga Normal</th>
                                <th class="px-4 py-2.5 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider text-right w-32">Harga Lama</th>
                                <th class="px-4 py-2.5 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider text-right w-32">Harga Baru</th>
                                <th class="px-4 py-2.5 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20 text-xs font-medium text-on-surface-variant" id="importPreviewBody">
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-on-surface-variant opacity-70">
                                    Belum ada file yang dipreview.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        <div class="px-6 py-3.5 bg-surface-container-low border-t border-outline-variant/30 flex justify-end items-center gap-2">
            <button class="inline-flex items-center justify-center px-4 rounded-lg bg-white border border-outline-variant hover:bg-surface-container-low text-on-surface text-xs font-semibold h-10 transition-all active:scale-95" onclick="closeImportPriceModal()" type="button">
                Batal
            </button>
            <button class="inline-flex items-center justify-center gap-1.5 px-5 rounded-lg bg-primary text-on-primary text-xs font-bold shadow-md shadow-primary/10 hover:bg-opacity-95 h-10 transition-all opacity-50 cursor-not-allowed active:scale-95" disabled id="executeImportButton" type="button" onclick="executeImportPricing()">
                <span class="material-symbols-outlined text-[18px]">done_all</span>
                Eksekusi Import
            </button>
        </div>

    </div>
</div>

<div class="fixed inset-0 z-[70] hidden items-center justify-center p-4" id="confirmSaveModal">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-xl"></div>
    <div class="relative w-full max-w-md bg-white rounded-2xl border border-outline-variant shadow-2xl overflow-hidden">
        <div class="p-6 border-b border-outline-variant bg-surface-container-low">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined">verified</span>
                </div>
                <div>
                    <h4 class="font-headline-md text-headline-md text-on-surface">Konfirmasi Eksekusi Harga</h4>
                    <p class="text-label-md text-on-surface-variant">Validasi akhir sebelum data ditulis.</p>
                </div>
            </div>
        </div>
        <div class="p-6">
            <p class="font-body-md text-body-md text-on-surface" id="confirmSummaryText">Apakah Anda yakin ingin
                mengeksekusi harga baru?</p>
        </div>
        <div class="px-6 py-4 bg-surface-container-low flex justify-end gap-3">
            <button
                class="px-5 py-2.5 rounded-xl border border-outline-variant text-on-surface font-bold hover:bg-white"
                type="button" onclick="hideConfirmModal()">Periksa Lagi</button>
            <button class="px-6 py-2.5 rounded-xl bg-primary text-on-primary font-bold hover:bg-opacity-90"
                type="button" onclick="executePricingSubmit()">Ya, Eksekusi</button>
        </div>
    </div>
</div>

<script>
const tokoTargets = @json($tokoTargets);
const salesTargets = @json($salesTargets);
const initialRows = @json($initialRows ?? []);
const pricingDataUrl = "{{ route('pricing.data') }}";
const pricingModalProductsUrl = "{{ route('pricing.modal_products') }}";
const pricingImportTemplateUrl = "{{ route('pricing.import_template') }}";
const pricingImportPreviewUrl = "{{ route('pricing.import_preview') }}";
const pricingImportExecuteUrl = "{{ route('pricing.import_execute') }}";
const csrfToken = "{{ csrf_token() }}";

const categorySelect = document.getElementById('categorySelect');
const targetSelect = document.getElementById('targetSelect');
const targetContainer = document.getElementById('targetContainer');
const targetLabel = document.getElementById('targetLabel');
const tableSearch = document.getElementById('tableSearch');
const tableHeader = document.getElementById('tableHeader');
const tableBody = document.getElementById('tableBody');
const mainActionButton = document.getElementById('mainActionButton');
const buttonText = document.getElementById('buttonText');
const downloadTemplateButton = document.getElementById('downloadTemplateButton');
const importPriceButton = document.getElementById('importPriceButton');
const pricingModal = document.getElementById('pricingModal');
const pricingModalPanel = document.getElementById('pricingModalPanel');
const modalTitle = document.getElementById('modalTitle');
const modalSubtitle = document.getElementById('modalSubtitle');
const modalTableBody = document.getElementById('modalTableBody');
const modalRowInfo = document.getElementById('modalRowInfo');
const modalProductSearch = document.getElementById('modalProductSearch');
const modalPrevPageBtn = document.getElementById('modalPrevPageBtn');
const modalNextPageBtn = document.getElementById('modalNextPageBtn');
const modalPaginationInfo = document.getElementById('modalPaginationInfo');
const formBulkPricing = document.getElementById('formBulkPricing');
const formCategory = document.getElementById('formCategory');
const formTargetId = document.getElementById('formTargetId');
const modalRefColLabel = document.getElementById('modalRefColLabel');
const submitFormBtn = document.getElementById('submitFormBtn');
const confirmSaveModal = document.getElementById('confirmSaveModal');
const confirmSummaryText = document.getElementById('confirmSummaryText');
const importPriceModal = document.getElementById('importPriceModal');
const formImportPreview = document.getElementById('formImportPreview');
const importFileInput = document.getElementById('importFileInput');
const importTargetInfo = document.getElementById('importTargetInfo');
const importSummaryBox = document.getElementById('importSummaryBox');
const importSummaryText = document.getElementById('importSummaryText');
const importMessageText = document.getElementById('importMessageText');
const importPreviewBody = document.getElementById('importPreviewBody');
const executeImportButton = document.getElementById('executeImportButton');

let searchTimer = null;
let modalSearchTimer = null;
let latestModalRows = [];
let modalCurrentPage = 1;
let modalLastPage = 1;
let modalPerPage = 10;
let modalTotalRows = 0;
let editedPricingMap = {};
let importPreviewToken = null;

document.addEventListener('DOMContentLoaded', function() {
    handleCategoryChange('all', false);
    if (initialRows && initialRows.length > 0) {
        renderPricingTable('all', initialRows, '');
    } else {
        applyFilters();
    }

    targetSelect.addEventListener('change', function() {
        updateActionButtonState();
        applyFilters();
    });

    tableSearch.addEventListener('input', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            applyFilters();
        }, 250);
    });

    if (modalProductSearch) {
        modalProductSearch.addEventListener('input', function() {
            clearTimeout(modalSearchTimer);
            modalSearchTimer = setTimeout(function() {
                modalCurrentPage = 1;
                loadModalProducts();
            }, 250);
        });
    }

    if (modalPrevPageBtn) {
        modalPrevPageBtn.addEventListener('click', function() {
            if (modalCurrentPage > 1) {
                modalCurrentPage--;
                loadModalProducts();
            }
        });
    }

    if (modalNextPageBtn) {
        modalNextPageBtn.addEventListener('click', function() {
            if (modalCurrentPage < modalLastPage) {
                modalCurrentPage++;
                loadModalProducts();
            }
        });
    }

    submitFormBtn.addEventListener('click', function(event) {
        event.preventDefault();
        showConfirmModal();
    });

    if (formImportPreview) {
        formImportPreview.addEventListener('submit', function(event) {
            event.preventDefault();
            previewImportPricing();
        });
    }
});

function handleCategoryChange(value, shouldApply = true) {
    if (value === 'toko') {
        targetContainer.classList.remove('opacity-50', 'pointer-events-none');
        targetLabel.textContent = 'Target Toko';
        targetSelect.disabled = false;
        populateTargetSelect(tokoTargets, 'Pilih Toko Mitra...');
    } else if (value === 'sales') {
        targetContainer.classList.remove('opacity-50', 'pointer-events-none');
        targetLabel.textContent = 'Target Sales';
        targetSelect.disabled = false;
        populateTargetSelect(salesTargets, 'Pilih Sales Lapangan...');
    } else {
        targetContainer.classList.add('opacity-50', 'pointer-events-none');
        targetLabel.textContent = 'Target';
        targetSelect.disabled = true;
        targetSelect.innerHTML = '<option value="">Tidak membutuhkan target</option>';
    }

    updateActionButtonState();

    if (shouldApply) {
        applyFilters();
    }
}

function populateTargetSelect(items, placeholder) {
    targetSelect.innerHTML = '';
    const firstOption = document.createElement('option');
    firstOption.value = '';
    firstOption.textContent = placeholder;
    targetSelect.appendChild(firstOption);

    items.forEach(function(item) {
        const option = document.createElement('option');
        option.value = item.id;
        option.textContent = item.name;
        targetSelect.appendChild(option);
    });
}

function updateActionButtonState() {
    const category = categorySelect.value;
    const targetName = getSelectedTargetName();

    mainActionButton.disabled = false;
    mainActionButton.classList.remove('opacity-50', 'cursor-not-allowed');

    if (category === 'all') {
        buttonText.textContent = 'Pilih Kategori Harga';
        mainActionButton.disabled = true;
        mainActionButton.classList.add('opacity-50', 'cursor-not-allowed');
    } else if (category === 'normal') {
        buttonText.textContent = 'Atur Harga Normal';
    } else if (category === 'toko') {
        buttonText.textContent = targetSelect.value ? `Atur Harga Toko: ${targetName}` : 'Pilih Toko Dahulu';
    } else if (category === 'sales') {
        buttonText.textContent = targetSelect.value ? `Atur Harga Sales: ${targetName}` : 'Pilih Sales Dahulu';
    }

    updateImportButtonsState();
}


function updateImportButtonsState() {
    const category = categorySelect.value;
    const canImport = (category === 'toko' || category === 'sales') && Boolean(targetSelect.value);

    [downloadTemplateButton, importPriceButton].forEach(function(button) {
        if (!button) return;
        button.disabled = !canImport;
        button.classList.remove('opacity-40', 'opacity-50');
        button.classList.toggle('tb-pricing-action-disabled', !canImport);
        button.classList.toggle('cursor-not-allowed', !canImport);
        button.classList.toggle('cursor-pointer', canImport);
    });
}

function getSpecialImportParams() {
    return {
        category: categorySelect.value,
        target_id: targetSelect.value || '',
        target_name: getSelectedTargetName()
    };
}

function validateSpecialImportSelection() {
    const params = getSpecialImportParams();

    if (params.category !== 'toko' && params.category !== 'sales') {
        alert('Import harga khusus hanya untuk kategori Toko Spesifik atau Sales Spesifik.');
        return false;
    }

    if (!params.target_id) {
        alert('Pilih target terlebih dahulu sebelum import harga khusus.');
        return false;
    }

    return true;
}

function downloadImportTemplate() {
    if (!validateSpecialImportSelection()) {
        return;
    }

    const params = new URLSearchParams();
    params.set('category', categorySelect.value);
    params.set('target_id', targetSelect.value || '');

    window.location.href = `${pricingImportTemplateUrl}?${params.toString()}`;
}

function openImportPriceModal() {
    if (!validateSpecialImportSelection()) {
        return;
    }

    importPreviewToken = null;
    importFileInput.value = '';
    importTargetInfo.textContent =
        `${categorySelect.value === 'toko' ? 'Toko' : 'Sales'}: ${getSelectedTargetName()}.`;
    importSummaryBox.classList.add('hidden');
    importSummaryText.textContent = 'Belum ada preview.';
    importMessageText.textContent = '';
    importPreviewBody.innerHTML =
        '<tr><td colspan="7" class="px-6 py-10 text-center text-on-surface-variant">Belum ada file yang dipreview.</td></tr>';
    setExecuteImportState(false);

    importPriceModal.classList.remove('hidden');
    importPriceModal.classList.add('flex');
}

function closeImportPriceModal() {
    importPriceModal.classList.add('hidden');
    importPriceModal.classList.remove('flex');
}

async function previewImportPricing() {
    if (!validateSpecialImportSelection()) {
        return;
    }

    if (!importFileInput.files.length) {
        alert('Pilih file template CSV terlebih dahulu.');
        return;
    }

    const formData = new FormData();
    formData.append('category', categorySelect.value);
    formData.append('target_id', targetSelect.value || '');
    formData.append('template_file', importFileInput.files[0]);

    importPreviewBody.innerHTML =
        '<tr><td colspan="7" class="px-6 py-10 text-center text-on-surface-variant">Memvalidasi file import...</td></tr>';
    importSummaryBox.classList.remove('hidden');
    importSummaryText.textContent = 'Memproses preview import...';
    importMessageText.textContent = '';
    setExecuteImportState(false);
    importPreviewToken = null;

    try {
        const response = await fetch(pricingImportPreviewUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            body: formData
        });

        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Preview import gagal.');
        }

        importPreviewToken = payload.token || null;
        renderImportPreview(payload);
        setExecuteImportState(Boolean(importPreviewToken));
    } catch (error) {
        importSummaryBox.classList.remove('hidden');
        importSummaryText.textContent = 'Preview import gagal.';
        importMessageText.textContent = error.message || 'Terjadi kesalahan saat membaca file.';
        importPreviewBody.innerHTML =
            '<tr><td colspan="7" class="px-6 py-10 text-center text-error">Gagal memvalidasi file import.</td></tr>';
        setExecuteImportState(false);
    }
}

function renderImportPreview(payload) {
    const summary = payload.summary || {
        valid: 0,
        skip: 0,
        error: 0,
        total: 0
    };
    const rows = payload.rows || [];

    importSummaryBox.classList.remove('hidden');
    importSummaryText.textContent =
        `Total ${summary.total || 0} baris. Valid ${summary.valid || 0}, dilewati ${summary.skip || 0}, error ${summary.error || 0}.`;
    importMessageText.textContent = summary.valid > 0 ?
        'Hanya baris valid yang akan dieksekusi.' :
        'Tidak ada baris valid untuk dieksekusi.';

    if (!rows.length) {
        importPreviewBody.innerHTML =
            '<tr><td colspan="7" class="px-6 py-10 text-center text-on-surface-variant">Tidak ada data preview.</td></tr>';
        return;
    }

    importPreviewBody.innerHTML = rows.map(function(row) {
        const statusClass = row.status === 'valid' ?
            'bg-primary/10 text-primary' :
            (row.status === 'skip' ? 'bg-surface-container-high text-on-surface-variant' :
                'bg-error/10 text-error');
        const statusText = row.status === 'valid' ? 'Valid' : (row.status === 'skip' ? 'Skip' : 'Error');
        const hargaLama = row.harga_khusus_lama === null || typeof row.harga_khusus_lama === 'undefined' ? '-' :
            `Rp ${formatRupiah(row.harga_khusus_lama)}`;
        const hargaBaru = row.harga_khusus_baru === null || typeof row.harga_khusus_baru === 'undefined' ? '-' :
            `Rp ${formatRupiah(row.harga_khusus_baru)}`;

        return `
                <tr class="hover:bg-surface-container-low transition-colors">
                    <td class="px-4 py-3 text-on-surface-variant">${escapeHtml(row.row_number || '-')}</td>
                    <td class="px-4 py-3"><span class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase ${statusClass}">${statusText}</span></td>
                    <td class="px-4 py-3">
                        <p class="font-bold text-on-surface">${escapeHtml(row.nama_barang || '-')}</p>
                        <p class="text-label-sm text-on-surface-variant font-mono">${escapeHtml(row.kode_barang || '-')}</p>
                    </td>
                    <td class="px-4 py-3 text-right text-on-surface-variant">Rp ${formatRupiah(row.harga_jual_normal || 0)}</td>
                    <td class="px-4 py-3 text-right text-on-surface-variant">${hargaLama}</td>
                    <td class="px-4 py-3 text-right font-bold text-primary">${hargaBaru}</td>
                    <td class="px-4 py-3 text-on-surface-variant">${escapeHtml(row.keterangan || '-')}</td>
                </tr>
            `;
    }).join('');
}

function setExecuteImportState(enabled) {
    executeImportButton.disabled = !enabled;
    executeImportButton.classList.toggle('opacity-50', !enabled);
    executeImportButton.classList.toggle('cursor-not-allowed', !enabled);
}

async function executeImportPricing() {
    if (!importPreviewToken) {
        alert('Preview import belum valid. Upload dan preview file terlebih dahulu.');
        return;
    }

    if (!confirm('Eksekusi semua baris valid dari preview import ini?')) {
        return;
    }

    setExecuteImportState(false);

    try {
        const response = await fetch(pricingImportExecuteUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                token: importPreviewToken
            })
        });

        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Eksekusi import gagal.');
        }

        alert(payload.message || 'Import harga khusus berhasil.');
        importPreviewToken = null;
        closeImportPriceModal();
        await applyFilters();
    } catch (error) {
        alert(error.message || 'Terjadi kesalahan saat eksekusi import.');
        setExecuteImportState(true);
    }
}

function resetFilters() {
    categorySelect.value = 'all';
    tableSearch.value = '';
    handleCategoryChange('all', true);
}

async function applyFilters() {
    const params = new URLSearchParams();
    params.set('category', categorySelect.value);
    params.set('target_id', targetSelect.value || '');
    params.set('q', tableSearch.value || '');

    tableBody.innerHTML =
        '<tr><td colspan="6" class="px-6 py-10 text-center text-on-surface-variant">Memuat data harga...</td></tr>';

    try {
        const response = await fetch(`${pricingDataUrl}?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const payload = await response.json();
        renderPricingTable(categorySelect.value, payload.rows || [], payload.message || '');
    } catch (error) {
        renderTableError('Gagal memuat data harga. Periksa koneksi database dan route pricing.');
    }
}

let currentMainCategory = 'all';
let currentMainRowsList = [];
let currentMainPage = 1;
const mainPerPage = 20;

function renderPricingTable(category, rows, message) {
    currentMainCategory = category;
    currentMainRowsList = rows || [];
    currentMainPage = 1;

    if (category === 'all') {
        renderAllHeader();
    } else if (category === 'normal') {
        renderNormalHeader();
    } else {
        renderSpecialHeader();
    }

    renderMainPageTable(message);
}

function changeMainPage(direction) {
    const totalPages = Math.max(1, Math.ceil(currentMainRowsList.length / mainPerPage));
    const newPage = currentMainPage + direction;

    if (newPage >= 1 && newPage <= totalPages) {
        currentMainPage = newPage;
        renderMainPageTable();
    }
}

function renderMainPageTable(message) {
    const totalRows = currentMainRowsList.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / mainPerPage));
    const summarySpan = document.getElementById('mainPaginationSummary');
    const pageInfoSpan = document.getElementById('mainPageInfo');
    const prevBtn = document.getElementById('mainPrevPageBtn');
    const nextBtn = document.getElementById('mainNextPageBtn');

    if (!totalRows) {
        const colspan = 6;
        const emptyText = message || (currentMainCategory === 'all' ? 'Belum ada data harga.' : 'Belum ada data untuk filter ini.');
        tableBody.innerHTML = `<tr><td colspan="${colspan}" class="px-6 py-12 text-center text-on-surface-variant">${escapeHtml(emptyText)}</td></tr>`;
        if (summarySpan) summarySpan.textContent = 'Menampilkan 0 data';
        if (pageInfoSpan) pageInfoSpan.textContent = 'Halaman 1 dari 1';
        if (prevBtn) prevBtn.disabled = true;
        if (nextBtn) nextBtn.disabled = true;
        return;
    }

    const startIndex = (currentMainPage - 1) * mainPerPage;
    const endIndex = Math.min(startIndex + mainPerPage, totalRows);
    const pageRows = currentMainRowsList.slice(startIndex, endIndex);

    if (summarySpan) {
        summarySpan.textContent = `Menampilkan ${startIndex + 1}-${endIndex} dari ${totalRows} data barang`;
    }
    if (pageInfoSpan) {
        pageInfoSpan.textContent = `Halaman ${currentMainPage} dari ${totalPages}`;
    }
    if (prevBtn) prevBtn.disabled = currentMainPage <= 1;
    if (nextBtn) nextBtn.disabled = currentMainPage >= totalPages;

    if (currentMainCategory === 'all') {
        tableBody.innerHTML = pageRows.map(function(row, index) {
            const globalIndex = startIndex + index + 1;
            const badgeClass = row.kategori_harga === 'Normal' ? 'bg-primary/10 text-primary' : (row.kategori_harga === 'Toko' ? 'bg-secondary/10 text-secondary' : 'bg-tertiary-container/20 text-tertiary');
            const actionCategory = row.kategori_harga === 'Normal' ? 'normal' : row.kategori_harga.toLowerCase();
            const targetId = row.id_target || '';
            return `
                <tr class="hover:bg-surface-container-low transition-colors">
                    <td class="px-6 py-4 text-on-surface-variant">${globalIndex}</td>
                    <td class="px-6 py-4">
                        <p class="font-bold text-on-surface">${escapeHtml(row.nama_barang || '-')}</p>
                        <p class="text-label-sm text-on-surface-variant font-mono">${escapeHtml(row.kode_barang || '-')}</p>
                    </td>
                    <td class="px-6 py-4"><span class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase ${badgeClass}">${escapeHtml(row.kategori_harga || '-')}</span></td>
                    <td class="px-6 py-4 text-on-surface-variant">${escapeHtml(row.berlaku_untuk || '-')}</td>
                    <td class="px-6 py-4 font-bold text-on-surface">Rp ${formatRupiah(row.nominal_harga || 0)}</td>
                    <td class="px-6 py-4 text-right">
                        <button class="inline-flex items-center justify-center w-9 h-9 rounded-full hover:bg-surface-container-high text-primary" type="button" onclick="quickOpenFromTable('${actionCategory}', '${targetId}')" title="Edit Harga">
                            <span class="material-symbols-outlined text-[20px]">edit</span>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    } else if (currentMainCategory === 'normal') {
        tableBody.innerHTML = pageRows.map(function(row, index) {
            const globalIndex = startIndex + index + 1;
            return `
                <tr class="hover:bg-surface-container-low transition-colors">
                    <td class="px-6 py-4 text-on-surface-variant">${globalIndex}</td>
                    <td class="px-6 py-4">
                        <p class="font-bold text-on-surface">${escapeHtml(row.nama_barang || '-')}</p>
                        <p class="text-label-sm text-on-surface-variant font-mono">${escapeHtml(row.kode_barang || '-')}</p>
                    </td>
                    <td class="px-6 py-4 text-right text-on-surface-variant">Rp ${formatRupiah(row.harga_modal || 0)}</td>
                    <td class="px-6 py-4 text-right font-bold text-primary">Rp ${formatRupiah(row.harga_jual_normal || 0)}</td>
                    <td class="px-6 py-4"><span class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase bg-primary/10 text-primary">Normal</span></td>
                    <td class="px-6 py-4 text-right">
                        <button class="inline-flex items-center justify-center w-9 h-9 rounded-full hover:bg-surface-container-high text-primary" type="button" onclick="quickOpenFromTable('normal', '')" title="Edit Harga Normal">
                            <span class="material-symbols-outlined text-[20px]">edit</span>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    } else {
        tableBody.innerHTML = pageRows.map(function(row, index) {
            const globalIndex = startIndex + index + 1;
            const specialClass = currentMainCategory === 'toko' ? 'bg-secondary/10 text-secondary' : 'bg-tertiary-container/20 text-tertiary';
            return `
                <tr class="hover:bg-surface-container-low transition-colors">
                    <td class="px-6 py-4 text-on-surface-variant">${globalIndex}</td>
                    <td class="px-6 py-4">
                        <p class="font-bold text-on-surface">${escapeHtml(row.nama_barang || '-')}</p>
                        <p class="text-label-sm text-on-surface-variant font-mono">${escapeHtml(row.kode_barang || '-')}</p>
                    </td>
                    <td class="px-6 py-4 text-on-surface-variant">${escapeHtml(row.berlaku_untuk || '-')}</td>
                    <td class="px-6 py-4 text-right text-on-surface-variant">Rp ${formatRupiah(row.harga_jual_normal || 0)}</td>
                    <td class="px-6 py-4 text-right font-bold text-primary">Rp ${formatRupiah(row.harga_khusus || 0)}</td>
                    <td class="px-6 py-4 text-right">
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase ${specialClass}">${currentMainCategory === 'toko' ? 'Toko' : 'Sales'}</span>
                        <button class="ml-2 inline-flex items-center justify-center w-9 h-9 rounded-full hover:bg-surface-container-high text-primary" type="button" onclick="quickOpenFromTable('${currentMainCategory}', '${targetSelect.value || row.id_target || ''}')" title="Edit Harga Khusus">
                            <span class="material-symbols-outlined text-[20px]">edit</span>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }
}

function renderAllHeader() {
    tableHeader.innerHTML = `
            <th class="px-6 py-4 font-label-md text-on-surface-variant uppercase">No</th>
            <th class="px-6 py-4 font-label-md text-on-surface-variant uppercase">Produk</th>
            <th class="px-6 py-4 font-label-md text-on-surface-variant uppercase">Kategori Harga</th>
            <th class="px-6 py-4 font-label-md text-on-surface-variant uppercase">Berlaku Untuk</th>
            <th class="px-6 py-4 font-label-md text-on-surface-variant uppercase">Nominal Harga</th>
            <th class="px-6 py-4 font-label-md text-on-surface-variant uppercase text-right">Aksi</th>
        `;
}

function renderNormalHeader() {
    tableHeader.innerHTML = `
            <th class="px-6 py-4 font-label-md text-on-surface-variant uppercase">No</th>
            <th class="px-6 py-4 font-label-md text-on-surface-variant uppercase">Produk</th>
            <th class="px-6 py-4 font-label-md text-on-surface-variant uppercase text-right">Harga Modal</th>
            <th class="px-6 py-4 font-label-md text-on-surface-variant uppercase text-right">Harga Jual Normal</th>
            <th class="px-6 py-4 font-label-md text-on-surface-variant uppercase">Kategori</th>
            <th class="px-6 py-4 font-label-md text-on-surface-variant uppercase text-right">Aksi</th>
        `;
}

function renderSpecialHeader() {
    tableHeader.innerHTML = `
            <th class="px-6 py-4 font-label-md text-on-surface-variant uppercase">No</th>
            <th class="px-6 py-4 font-label-md text-on-surface-variant uppercase">Produk</th>
            <th class="px-6 py-4 font-label-md text-on-surface-variant uppercase">Berlaku Untuk</th>
            <th class="px-6 py-4 font-label-md text-on-surface-variant uppercase text-right">Harga Jual Normal</th>
            <th class="px-6 py-4 font-label-md text-on-surface-variant uppercase text-right">Harga Khusus Target</th>
            <th class="px-6 py-4 font-label-md text-on-surface-variant uppercase text-right">Aksi</th>
        `;
}

function renderTableError(message) {
    renderAllHeader();
    tableBody.innerHTML =
        `<tr><td colspan="6" class="px-6 py-12 text-center text-error">${escapeHtml(message)}</td></tr>`;
}

async function quickOpenFromTable(category, targetId) {
    categorySelect.value = category;
    handleCategoryChange(category, false);

    if (targetId) {
        targetSelect.value = targetId;
    }

    updateActionButtonState();
    await applyFilters();
    openPricingModal();
}

async function openPricingModal() {
    const category = categorySelect.value;
    const targetId = targetSelect.value || '';

    if (category === 'all') {
        alert('Pilih kategori User / Normal, Toko Spesifik, atau Sales Spesifik sebelum mengatur harga.');
        return;
    }

    if ((category === 'toko' || category === 'sales') && !targetId) {
        alert('Pilih target terlebih dahulu sebelum mengatur harga khusus.');
        return;
    }

    formCategory.value = category;
    formTargetId.value = targetId;
    editedPricingMap = {};
    modalCurrentPage = 1;
    modalLastPage = 1;
    modalTotalRows = 0;
    if (modalProductSearch) {
        modalProductSearch.value = '';
    }
    modalTableBody.innerHTML =
        '<tr><td colspan="4" class="px-6 py-12 text-center text-on-surface-variant">Memuat seluruh daftar barang...</td></tr>';
    modalRowInfo.textContent = 'Memuat produk...';

    if (category === 'normal') {
        modalTitle.textContent = 'Atur Harga Jual Normal';
        modalSubtitle.textContent = 'Seluruh barang ditampilkan. Klik ikon edit pada baris yang ingin diubah.';
        modalRefColLabel.textContent = 'Harga Modal';
    } else {
        modalTitle.textContent = `Atur Harga Khusus: ${getSelectedTargetName()}`;
        modalSubtitle.textContent =
            'Seluruh barang ditampilkan. Harga khusus lama ikut dimuat bila sudah pernah disimpan.';
        modalRefColLabel.textContent = 'Harga Jual Normal';
    }

    pricingModal.classList.remove('opacity-0', 'pointer-events-none');
    setTimeout(function() {
        pricingModalPanel.classList.remove('translate-y-4');
    }, 10);

    await loadModalProducts();
}

function closePricingModal() {
    pricingModalPanel.classList.add('translate-y-4');
    setTimeout(function() {
        pricingModal.classList.add('opacity-0', 'pointer-events-none');
    }, 180);
}

async function loadModalProducts() {
    const params = new URLSearchParams();
    params.set('category', categorySelect.value);
    params.set('target_id', targetSelect.value || '');
    params.set('search', modalProductSearch ? modalProductSearch.value.trim() : '');
    params.set('page', String(modalCurrentPage));
    params.set('per_page', String(modalPerPage));

    try {
        modalTableBody.innerHTML =
            '<tr><td colspan="4" class="px-6 py-12 text-center text-on-surface-variant">Memuat daftar barang...</td></tr>';
        modalRowInfo.textContent = 'Memuat produk...';

        const response = await fetch(`${pricingModalProductsUrl}?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const payload = await response.json();
        latestModalRows = payload.rows || [];
        const pagination = payload.pagination || {
            current_page: 1,
            last_page: 1,
            total: latestModalRows.length,
            from: latestModalRows.length ? 1 : 0,
            to: latestModalRows.length,
            per_page: modalPerPage
        };

        modalCurrentPage = Number(pagination.current_page || 1);
        modalLastPage = Number(pagination.last_page || 1);
        modalTotalRows = Number(pagination.total || 0);
        modalPerPage = Number(pagination.per_page || 10);

        renderModalProducts(latestModalRows);
        renderModalPagination(pagination);
    } catch (error) {
        latestModalRows = [];
        modalTableBody.innerHTML =
            '<tr><td colspan="4" class="px-6 py-12 text-center text-error">Gagal memuat daftar barang.</td></tr>';
        modalRowInfo.textContent = '0 produk dimuat.';
        renderModalPagination({
            current_page: 1,
            last_page: 1,
            total: 0,
            from: 0,
            to: 0
        });
    }
}

function renderModalProducts(rows) {
    if (!rows.length) {
        const keyword = modalProductSearch ? modalProductSearch.value.trim() : '';
        const message = keyword ? 'Produk tidak ditemukan untuk kata kunci tersebut.' :
            'Belum ada produk di tbl_barang.';
        modalTableBody.innerHTML =
            `<tr><td colspan="4" class="px-6 py-12 text-center text-on-surface-variant">${escapeHtml(message)}</td></tr>`;
        modalRowInfo.textContent = '0 produk pada halaman ini.';
        return;
    }

    modalTableBody.innerHTML = rows.map(function(row) {
        const key = makeRowKey(row.kode_barang || '');
        const hasEditedValue = Object.prototype.hasOwnProperty.call(editedPricingMap, row.kode_barang || '');
        const activePrice = hasEditedValue ?
            formatRupiah(editedPricingMap[row.kode_barang || '']) :
            (row.harga_aktif === null || typeof row.harga_aktif === 'undefined' ? '' : formatRupiah(row
                .harga_aktif));
        const statusBadge = row.is_set ?
            '<span class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-bold bg-primary/10 text-primary uppercase">Sudah Set</span>' :
            '<span class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-bold bg-error/10 text-error uppercase">Belum Set</span>';
        const inputClasses = hasEditedValue ?
            'price-input w-40 text-right bg-white border border-primary rounded-xl px-4 py-2.5 text-on-surface ring-2 ring-primary/20 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none' :
            'price-input w-40 text-right bg-surface-container-low border border-outline-variant rounded-xl px-4 py-2.5 text-on-surface-variant cursor-not-allowed focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none';
        const readOnlyAttr = hasEditedValue ? '' : 'readonly';
        const rowClass = hasEditedValue ? 'hover:bg-surface-container-low transition-colors bg-primary/5' :
            'hover:bg-surface-container-low transition-colors';

        return `
                <tr id="modal-row-${key}" class="${rowClass}">
                    <td class="px-6 py-4">
                        <p class="font-bold text-on-surface">${escapeHtml(row.nama_barang || '-')} ${statusBadge}</p>
                        <p class="text-label-sm text-on-surface-variant font-mono">${escapeHtml(row.kode_barang || '-')}</p>
                    </td>
                    <td class="px-4 py-4 text-right font-bold text-on-surface-variant">Rp ${formatRupiah(row.harga_acuan || 0)}</td>
                    <td class="px-4 py-4 text-right">
                        <input class="${inputClasses}" id="price-input-${key}" data-kode-barang="${escapeAttribute(row.kode_barang || '')}" ${readOnlyAttr} type="text" value="${activePrice}" oninput="handlePriceTyping(this)"/>
                    </td>
                    <td class="px-4 py-4 text-center">
                        <button class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-surface-container-high hover:bg-primary hover:text-on-primary text-primary transition-all" type="button" onclick="enablePriceEdit('${key}')" title="Edit baris ini">
                            <span class="material-symbols-outlined text-[20px]">edit_square</span>
                        </button>
                    </td>
                </tr>
            `;
    }).join('');
}

function renderModalPagination(pagination) {
    const current = Number(pagination.current_page || 1);
    const last = Number(pagination.last_page || 1);
    const total = Number(pagination.total || 0);
    const from = Number(pagination.from || 0);
    const to = Number(pagination.to || 0);
    const changedCount = countChangedRows();

    if (modalPaginationInfo) {
        modalPaginationInfo.textContent = `Halaman ${current} / ${last}`;
    }
    if (modalPrevPageBtn) {
        modalPrevPageBtn.disabled = current <= 1;
    }
    if (modalNextPageBtn) {
        modalNextPageBtn.disabled = current >= last;
    }
    if (total === 0) {
        modalRowInfo.textContent = changedCount > 0 ?
            `${changedCount} item sudah diedit. Tidak ada produk pada hasil pencarian ini.` : '0 produk ditemukan.';
    } else {
        modalRowInfo.textContent = `Menampilkan ${from}-${to} dari ${total} produk. ${changedCount} item sudah diedit.`;
    }
}

function enablePriceEdit(key) {
    const input = document.getElementById(`price-input-${key}`);
    const row = document.getElementById(`modal-row-${key}`);

    if (!input) {
        return;
    }

    input.readOnly = false;
    input.classList.remove('bg-surface-container-low', 'text-on-surface-variant', 'cursor-not-allowed');
    input.classList.add('bg-white', 'text-on-surface', 'ring-2', 'ring-primary/20', 'border-primary');

    if (row) {
        row.classList.add('bg-primary/5');
    }

    const kodeBarang = input.dataset.kodeBarang || '';
    const clean = input.value.replace(/[^0-9]/g, '');
    if (kodeBarang && !Object.prototype.hasOwnProperty.call(editedPricingMap, kodeBarang)) {
        editedPricingMap[kodeBarang] = clean;
    }

    input.focus();
    input.select();
    renderModalPagination({
        current_page: modalCurrentPage,
        last_page: modalLastPage,
        total: modalTotalRows,
        from: getModalFromValue(),
        to: getModalToValue()
    });
}

function handlePriceTyping(input) {
    const clean = input.value.replace(/[^0-9]/g, '');
    input.value = clean === '' ? '' : formatRupiah(clean);
    const kodeBarang = input.dataset.kodeBarang || '';
    if (kodeBarang) {
        editedPricingMap[kodeBarang] = clean;
    }
    renderModalPagination({
        current_page: modalCurrentPage,
        last_page: modalLastPage,
        total: modalTotalRows,
        from: getModalFromValue(),
        to: getModalToValue()
    });
}

function showConfirmModal() {
    const changedCount = countChangedRows();

    if (changedCount === 0) {
        alert('Belum ada harga yang diedit. Klik ikon edit pada baris produk yang ingin diubah.');
        return;
    }

    confirmSummaryText.textContent =
        `Apakah Anda yakin ingin mengeksekusi harga baru untuk ${changedCount} item produk ini?`;
    confirmSaveModal.classList.remove('hidden');
    confirmSaveModal.classList.add('flex');
}

function hideConfirmModal() {
    confirmSaveModal.classList.add('hidden');
    confirmSaveModal.classList.remove('flex');
}

function executePricingSubmit() {
    const oldGeneratedInputs = formBulkPricing.querySelectorAll('.generated-pricing-input');
    oldGeneratedInputs.forEach(function(node) {
        node.remove();
    });

    Object.keys(editedPricingMap).forEach(function(kodeBarang) {
        const cleanPrice = String(editedPricingMap[kodeBarang] || '').replace(/[^0-9]/g, '');
        if (!kodeBarang || cleanPrice === '') {
            return;
        }

        const kodeInput = document.createElement('input');
        kodeInput.type = 'hidden';
        kodeInput.name = 'kode_barang[]';
        kodeInput.value = kodeBarang;
        kodeInput.className = 'generated-pricing-input';
        formBulkPricing.appendChild(kodeInput);

        const hargaInput = document.createElement('input');
        hargaInput.type = 'hidden';
        hargaInput.name = 'harga_baru[]';
        hargaInput.value = cleanPrice;
        hargaInput.className = 'generated-pricing-input';
        formBulkPricing.appendChild(hargaInput);

        const changedInput = document.createElement('input');
        changedInput.type = 'hidden';
        changedInput.name = 'changed[]';
        changedInput.value = '1';
        changedInput.className = 'generated-pricing-input';
        formBulkPricing.appendChild(changedInput);
    });

    formBulkPricing.submit();
}

function countChangedRows() {
    let total = 0;
    Object.keys(editedPricingMap).forEach(function(kodeBarang) {
        const priceClean = String(editedPricingMap[kodeBarang] || '').replace(/[^0-9]/g, '');
        if (priceClean !== '') {
            total++;
        }
    });
    return total;
}

function getModalFromValue() {
    if (modalTotalRows === 0) {
        return 0;
    }
    return ((modalCurrentPage - 1) * modalPerPage) + 1;
}

function getModalToValue() {
    if (modalTotalRows === 0) {
        return 0;
    }
    return Math.min(modalCurrentPage * modalPerPage, modalTotalRows);
}

function makeRowKey(value) {
    const clean = String(value || '').replace(/[^a-zA-Z0-9_-]/g, '_');
    return clean === '' ? 'unknown' : clean;
}

function getSelectedTargetName() {
    if (!targetSelect.value) {
        return '';
    }
    const selected = targetSelect.options[targetSelect.selectedIndex];
    return selected ? selected.textContent : '';
}

function formatRupiah(value) {
    const numeric = Number(String(value || 0).replace(/[^0-9]/g, ''));
    return numeric.toLocaleString('id-ID');
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function escapeAttribute(value) {
    return escapeHtml(value).replace(/`/g, '&#096;');
}
</script>
@endsection