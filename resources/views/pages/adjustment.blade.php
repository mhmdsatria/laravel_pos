@extends('layouts.app')

@section('title', 'Stock Adjustment | Toko Bangunan 39')

@section('content')
<div class="space-y-lg">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <h2 class="font-headline-xl text-headline-xl text-on-surface uppercase text-primary">Stock Adjustment</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">Audit dan sinkronisasi stok sistem dengan stok fisik gudang.</p>
        </div>
        
        <div class="flex flex-wrap items-center gap-md">
            <form method="POST" action="{{ route('adjustment.sync_all') }}" onsubmit="return confirm('Sinkronkan semua data adjustment Pending ke stok barang sekarang? Proses ini akan mengubah stok sistem secara permanen.');">
                @csrf
                <button type="submit" class="bg-secondary text-on-secondary px-lg py-sm rounded-full font-label-md text-label-md flex items-center gap-2 hover:brightness-110 active:scale-95 transition-all shadow-md">
                    <span class="material-symbols-outlined text-sm">sync</span>
                    <span>Sinkronkan Stok All</span>
                </button>
            </form>

            <button
                id="openAdjustmentModalBtn"
                class="bg-primary text-on-primary px-lg py-sm rounded-full font-label-md text-label-md flex items-center gap-2 hover:brightness-110 active:scale-95 transition-all"
                type="button"
            >
                <span class="material-symbols-outlined text-sm">add</span>
                <span>Tambah Penyesuaian Baru</span>
            </button>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-gutter">
        <div class="bg-surface border border-outline-variant p-md rounded-xl space-y-2">
            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Total Opname (MoM)</p>
            <p class="font-headline-md text-headline-md text-on-surface">{{ number_format($totalOpname, 0, ',', '.') }} Records</p>
        </div>
        <div class="bg-surface border border-outline-variant p-md rounded-xl space-y-2">
            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Pending Sync</p>
            <p class="font-headline-md text-headline-md text-error">{{ number_format($pendingSync, 0, ',', '.') }} Items</p>
        </div>
        <div class="bg-surface border border-outline-variant p-md rounded-xl space-y-2">
            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Stock Shrinkage</p>
            <p class="font-headline-md text-headline-md text-error">Rp {{ number_format($stockShrinkage, 0, ',', '.') }}</p>
        </div>
        <div class="bg-surface border border-outline-variant p-md rounded-xl space-y-2">
            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Accuracy Rate</p>
            <p class="font-headline-md text-headline-md text-primary">{{ number_format($accuracyRate, 1, ',', '.') }}%</p>
        </div>
    </div>

    <div class="bg-surface border border-outline-variant rounded-xl overflow-hidden">
        <div class="px-lg py-md border-b border-outline-variant bg-surface-container-low flex flex-col md:flex-row md:items-center justify-between gap-md">
            <div>
                <h3 class="font-headline-md text-headline-md text-on-surface">Log Adjustment</h3>
                <p class="font-body-md text-body-md text-on-surface-variant">Daftar pengajuan koreksi dan riwayat sinkronisasi stok.</p>
            </div>
            <form method="GET" action="{{ route('adjustment.index') }}" class="relative w-full md:w-96">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-sm">search</span>
                <input class="w-full bg-surface-container-lowest border border-outline-variant rounded-full pl-10 pr-4 py-2 text-body-md font-body-md focus:ring-2 focus:ring-primary outline-none" id="globalSearch" name="search" value="{{ $search ?? request('search', '') }}" placeholder="Search adjustment logs..." type="text"/>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[1180px]" id="adjustmentTable">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant">
                        <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant w-12">NO</th>
                        <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant">TANGGAL</th>
                        <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant">NAMA BARANG</th>
                        <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant">SATUAN</th>
                        <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant">STOK SISTEM</th>
                        <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant">STOK FISIK</th>
                        <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant">SELISIH</th>
                        <!-- <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant">STAF GUDANG</th> -->
                        <!-- <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant">DIVERIFIKASI OLEH</th> -->
                        <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant">STATUS</th>
                        <!-- <th class="px-lg py-md font-label-md text-label-md text-on-surface-variant text-right">AKSI</th> -->
                    </tr>
                </thead>
                <tbody class="font-body-md text-body-md text-on-surface" id="tableBody">
                    @forelse ($adjustments as $key => $item)
                        <tr class="border-b border-outline-variant hover:bg-surface-container-low transition-colors group">
                            <td class="px-lg py-md">{{ $adjustments->firstItem() + $key }}</td>
                            <td class="px-lg py-md text-nowrap">{{ optional($item->tgl_adjustment)->format('d M Y H:i') }}</td>
                            <td class="px-lg py-md font-medium">{{ optional($item->product)->nama_barang ?? $item->kode_barang }}</td>
                            <td class="px-lg py-md">
                                <span class="bg-outline-variant/30 text-on-surface-variant font-label-sm text-label-sm px-2 py-1 rounded uppercase">
                                    {{ optional($item->product)->satuan ?? '-' }}
                                </span>
                            </td>
                            <td class="px-lg py-md">{{ format_qty($item->stok_sistem) }}</td>
                            <td class="px-lg py-md">{{ format_qty($item->stok_fisik) }}</td>
                            <td class="px-lg py-md font-bold @if($item->selisih < 0) text-error @elseif($item->selisih > 0) text-primary @else text-on-surface-variant @endif">
                                {{ $item->selisih > 0 ? '+' : '' }}{{ format_qty($item->selisih) }}
                            </td>
                            <!-- <td class="px-lg py-md">{{ $item->staf_gudang }}</td> -->
                            <!-- <td class="px-lg py-md text-on-surface-variant {{ $item->diverifikasi_oleh ? '' : 'italic' }}">
                                {{ $item->diverifikasi_oleh ?: '-' }}
                            </td> -->
                            <td class="px-lg py-md">
                                @if ($item->status === 'Pending')
                                    <span class="bg-error-container text-on-error-container font-label-sm text-label-sm px-2 py-1 rounded-full uppercase">Pending</span>
                                @else
                                    <span class="bg-primary-container/20 text-primary font-label-sm text-label-sm px-2 py-1 rounded-full uppercase">Synced</span>
                                @endif
                            </td>
                            <!-- <td class="px-lg py-md text-right">
                                <div class="flex items-center justify-end">
                                    @if ($item->status === 'Pending' && ($canSync ?? false))
                                        <form method="POST" action="{{ route('adjustment.sync', $item->id) }}" onsubmit="return confirm('Sinkronkan stok sistem ke stok fisik untuk barang ini?');">
                                            @csrf
                                            <button class="bg-primary text-on-primary px-3 py-1.5 rounded-lg font-label-md text-label-md inline-flex items-center gap-1 hover:brightness-110 transition-all" type="submit">
                                                <span class="material-symbols-outlined text-sm">sync</span>
                                                <span>Sinkronkan Stok</span>
                                            </button>
                                        </form>
                                    @elseif ($item->status === 'Pending')
                                        <span class="text-on-surface-variant font-label-sm text-label-sm italic">Menunggu supervisor</span>
                                    @else
                                        <button class="text-primary font-label-md text-label-md inline-flex items-center gap-1 hover:underline" onclick="showReadOnlyDetail({{ json_encode([
                                            'tanggal' => optional($item->tgl_adjustment)->format('d M Y H:i'),
                                            'barang' => optional($item->product)->nama_barang ?? $item->kode_barang,
                                            'satuan' => optional($item->product)->satuan ?? '-',
                                            'stok_sistem' => $item->stok_sistem,
                                            'stok_fisik' => $item->stok_fisik,
                                            'selisih' => $item->selisih,
                                            'keterangan' => $item->keterangan,
                                            'staf_gudang' => $item->staf_gudang,
                                            'diverifikasi_oleh' => $item->diverifikasi_oleh,
                                            'status' => $item->status,
                                        ]) }})" type="button">
                                            <span class="material-symbols-outlined text-sm">visibility</span>
                                            <span>Lihat Detail</span>
                                        </button>
                                    @endif
                                </div>
                            </td> -->
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-lg py-xl text-center text-on-surface-variant">
                                Belum ada data adjustment. Klik tombol Tambah Penyesuaian Baru untuk membuat draft opname.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-lg py-md bg-surface-container-lowest border-t border-outline-variant">
            {{ $adjustments->links() }}
        </div>
    </div>
</div>
@endsection

@push('modals')
{{-- Modal Form Adjustment --}}
<div
    id="adjustmentModal"
    class="fixed inset-0 z-50 hidden items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="adjustmentModalTitle"
>
    <div
        class="absolute inset-0 bg-black/45 backdrop-blur-sm"
        onclick="closeAdjustmentModal()"
    ></div>

    <div class="adjustment-panel relative z-10 flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-2xl">
        <div class="flex items-center justify-between border-b border-outline-variant bg-surface-container-lowest px-lg py-md">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-2xl text-primary">rule</span>
                <div>
                    <h3 id="adjustmentModalTitle" class="font-headline-md text-headline-md text-on-surface">
                        Form Penyesuaian Stok
                    </h3>
                    <p class="text-label-md text-on-surface-variant">
                        Catat stok fisik dan simpan sebagai draft adjustment.
                    </p>
                </div>
            </div>

            <button
                type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-on-surface-variant transition-colors hover:bg-error/10 hover:text-error"
                onclick="closeAdjustmentModal()"
                aria-label="Tutup modal"
            >
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="adjustmentForm" method="POST" action="{{ route('adjustment.draft') }}" class="flex min-h-0 flex-1 flex-col">
            @csrf

            <div class="min-h-0 flex-1 space-y-md overflow-y-auto p-lg">
                <div class="relative space-y-sm" id="comboboxContainer">
                    <label class="block font-label-md text-label-md text-on-surface-variant">
                        Cari & Pilih Barang <span class="text-error">*</span>
                    </label>

                    <div class="relative">
                        <span
                            class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 z-10 -translate-y-1/2 text-on-surface-variant"
                            style="font-size:20px;line-height:20px;"
                        >
                            search
                        </span>

                        <input
                            type="text"
                            id="comboboxInput"
                            class="block h-11 w-full rounded-xl border border-outline-variant bg-surface pl-12 pr-11 text-body-md text-on-surface outline-none transition-all placeholder:text-on-surface-variant/60 focus:border-primary focus:ring-4 focus:ring-primary/10"
                            placeholder="Ketik kode atau nama barang..."
                            autocomplete="off"
                            required
                        >

                        <span
                            id="comboboxArrow"
                            class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant transition-transform duration-200"
                        >
                            expand_more
                        </span>
                    </div>

                    <input type="hidden" name="kode_barang" id="productSelect" required>

                    <div
                        id="comboboxDropdown"
                        class="absolute left-0 right-0 z-50 mt-1 hidden max-h-60 overflow-y-auto rounded-xl border border-outline-variant bg-surface-container-lowest shadow-xl"
                    >
                        @foreach ($products as $product)
                            <button
                                type="button"
                                class="combobox-item flex w-full flex-col gap-0.5 border-b border-outline-variant/40 p-3 text-left transition-colors last:border-b-0 hover:bg-primary/10"
                                data-value="{{ $product->kode_barang }}"
                                data-satuan="{{ $product->satuan }}"
                                data-stok="{{ to_float($product->sisa_stok) }}"
                                data-nama="{{ $product->nama_barang }}"
                            >
                                <span class="font-mono text-xs font-bold tracking-wider text-primary">
                                    {{ $product->kode_barang }}
                                </span>
                                <span class="font-medium text-on-surface">
                                    {{ $product->nama_barang }}
                                </span>
                                <span class="text-xs text-on-surface-variant">
                                    Sisa stok:
                                    <strong>{{ format_qty($product->sisa_stok) }}</strong>
                                    {{ $product->satuan }}
                                </span>
                            </button>
                        @endforeach

                        <div id="comboboxEmpty" class="hidden p-4 text-center text-sm italic text-on-surface-variant">
                            Barang tidak ditemukan.
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block font-label-md text-label-md text-on-surface-variant">
                            Satuan
                        </label>
                        <input
                            id="product_satuan"
                            type="text"
                            value="-"
                            readonly
                            class="block h-11 w-full cursor-not-allowed rounded-xl border border-outline-variant bg-surface-container-low px-md text-body-md text-on-surface-variant"
                        >
                    </div>

                    <div>
                        <label class="mb-1 block font-label-md text-label-md text-on-surface-variant">
                            Stok Sistem
                        </label>
                        <input
                            id="product_stok_sistem"
                            type="text"
                            value="0"
                            readonly
                            class="block h-11 w-full cursor-not-allowed rounded-xl border border-outline-variant bg-surface-container-low px-md text-body-md text-on-surface-variant"
                        >
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block font-label-md text-label-md text-on-surface">
                            Stok Fisik <span class="text-error">*</span>
                        </label>
                        <input
                            name="stok_fisik"
                            id="stok_fisik"
                            type="number"
                            step="any"
                            min="0"
                            placeholder="Masukkan qty..."
                            required
                            class="block h-11 w-full rounded-xl border border-outline-variant bg-surface px-md text-body-md font-bold text-on-surface outline-none focus:border-primary focus:ring-4 focus:ring-primary/10"
                        >
                    </div>

                    <div>
                        <label class="mb-1 block font-label-md text-label-md text-on-surface-variant">
                            Preview Selisih
                        </label>
                        <div
                            id="preview_selisih_box"
                            class="flex h-11 items-center gap-2 rounded-xl border border-outline-variant bg-surface-container-low px-md"
                        >
                            <span id="preview_selisih_text" class="font-bold text-on-surface-variant">0</span>
                            <span id="preview_selisih_label" class="text-xs font-medium uppercase text-on-surface-variant">
                                Balanced
                            </span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block font-label-md text-label-md uppercase tracking-wider text-on-surface-variant">
                        Alasan / Keterangan <span class="text-error">*</span>
                    </label>
                    <textarea
                        id="keterangan"
                        name="keterangan"
                        rows="3"
                        required
                        placeholder="Contoh: barang rusak, selisih opname, atau koreksi pencatatan..."
                        class="block w-full resize-none rounded-xl border border-outline-variant bg-surface p-3 text-body-md text-on-surface outline-none focus:border-primary focus:ring-4 focus:ring-primary/10"
                    ></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-sm border-t border-outline-variant bg-surface-container-low px-lg py-md">
                <button
                    type="button"
                    class="tb-btn tb-btn-secondary"
                    onclick="closeAdjustmentModal()"
                >
                    Batal
                </button>

                <button type="submit" class="tb-btn tb-btn-primary">
                    <span class="material-symbols-outlined text-[18px]">save</span>
                    Simpan Draft
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Konfirmasi --}}
<div
    id="confirmAdjustmentModal"
    class="fixed inset-0 z-[70] hidden items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
>
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="hideConfirmAdjustmentModal()"></div>

    <div class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-2xl">
        <div class="border-b border-outline-variant bg-surface-container-low px-lg py-md">
            <h3 class="font-headline-md text-headline-md text-on-surface">
                Konfirmasi Draft Adjustment
            </h3>
            <p class="mt-1 text-body-md text-on-surface-variant">
                Periksa data sebelum disimpan.
            </p>
        </div>

        <div class="space-y-md p-lg">
            <p id="confirmAdjustmentText" class="text-body-md text-on-surface">
                Apakah data adjustment sudah benar?
            </p>

            <div class="space-y-sm rounded-xl border border-outline-variant bg-surface-container-low p-md">
                <div>
                    <p class="text-label-md text-on-surface-variant">Barang</p>
                    <p id="confirmProductName" class="font-bold text-on-surface">-</p>
                </div>
                <div>
                    <p class="text-label-md text-on-surface-variant">Selisih</p>
                    <p id="confirmSelisihValue" class="font-bold text-on-surface">0</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-sm border-t border-outline-variant bg-surface-container-low px-lg py-md">
            <button type="button" class="tb-btn tb-btn-secondary" onclick="hideConfirmAdjustmentModal()">
                Periksa Lagi
            </button>
            <button type="button" class="tb-btn tb-btn-primary" id="executeAdjustmentSubmitBtn">
                Ya, Simpan Draft
            </button>
        </div>
    </div>
</div>

{{-- Modal Detail --}}
<div class="fixed inset-0 z-[100] backdrop-blur-sm bg-black/50 items-center justify-center hidden p-4" id="detailAdjustmentModal" role="dialog" aria-modal="true">
    <div class="bg-white text-on-surface w-full max-w-xl h-auto max-h-[90vh] rounded-2xl overflow-hidden border border-outline-variant flex flex-col shadow-2xl transition-all duration-300">
        {{-- Modal Header --}}
        <div class="px-lg py-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <div class="flex items-center gap-3 text-primary">
                <span class="material-symbols-outlined text-2xl font-bold">read_more</span>
                <div>
                    <h3 class="font-headline-md text-headline-md font-bold text-on-surface">Detail Log Adjustment</h3>
                    <p class="text-xs text-on-surface-variant">Informasi audit penyesuaian stok & riwayat sinkronisasi.</p>
                </div>
            </div>
            <button class="w-9 h-9 flex items-center justify-center hover:bg-error/10 hover:text-error transition-colors rounded-full cursor-pointer" type="button" onclick="hideDetailAdjustmentModal()">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        {{-- Modal Body --}}
        <div id="detailAdjustmentContent" class="p-lg space-y-md overflow-y-auto max-h-[calc(90vh-130px)]"></div>

        {{-- Modal Footer --}}
        <div class="px-lg py-md border-t border-outline-variant flex justify-end bg-surface-container-low">
            <button type="button" class="tb-btn tb-btn-secondary" onclick="hideDetailAdjustmentModal()">
                Tutup
            </button>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
    // --- MANAJEMEN MODAL ADJUSTMENT ---
    function showAdjustmentModalElement() {
        const modal = document.getElementById('adjustmentModal');

        if (!modal) {
            console.error('Modal #adjustmentModal tidak ditemukan.');
            return false;
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');

        document.body.classList.add('modal-open');
        document.body.style.overflow = 'hidden';

        return true;
    }

    window.openAdjustmentModal = function () {
        if (!showAdjustmentModalElement()) {
            return;
        }

        setTimeout(function () {
            document.getElementById('comboboxInput')?.focus();
        }, 50);
    };

    window.closeAdjustmentModal = function () {
        const modal = document.getElementById('adjustmentModal');

        if (!modal) {
            return;
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');

        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';

        resetComboboxForm();
    };

    // --- VARIABEL & ELEMENT FORM ---
    const comboboxContainer = document.getElementById('comboboxContainer');
    const comboboxInput = document.getElementById('comboboxInput');
    const comboboxDropdown = document.getElementById('comboboxDropdown');
    const comboboxArrow = document.getElementById('comboboxArrow');
    const comboboxItems = document.querySelectorAll('.combobox-item');
    const comboboxEmpty = document.getElementById('comboboxEmpty');
    
    const hiddenProductInput = document.getElementById('productSelect'); // Input hidden penampung kode_barang
    const satuanInput = document.getElementById('product_satuan');
    const stokSistemInput = document.getElementById('product_stok_sistem');
    const stokFisikInput = document.getElementById('stok_fisik');
    const previewBox = document.getElementById('preview_selisih_box');
    const previewText = document.getElementById('preview_selisih_text');
    const previewLabel = document.getElementById('preview_selisih_label');
    const adjustmentForm = document.getElementById('adjustmentForm');
    const confirmAdjustmentModal = document.getElementById('confirmAdjustmentModal');
    const executeAdjustmentSubmitBtn = document.getElementById('executeAdjustmentSubmitBtn');
    let allowAdjustmentSubmit = false;

    function resetPreviewClass() {
        previewBox.className = 'w-full bg-surface-container-low border border-outline-variant rounded-lg px-md py-2.5 flex items-center gap-2 transition-all';
        previewText.className = 'font-body-md font-bold text-on-surface-variant';
        previewLabel.className = 'text-xs text-on-surface-variant font-medium uppercase';
    }

    // --- LOGIC CUSTOM SEARCHABLE DROPDOWN (COMBOBOX) ---
    if (comboboxInput && comboboxDropdown) {
        // Tampilkan dropdown saat input fokus atau diklik
        comboboxInput.addEventListener('focus', function() {
            comboboxDropdown.classList.remove('hidden');
            comboboxArrow.classList.add('rotate-180');
            filterCombobox(this.value);
        });

        function selectComboboxItem(item) {
            if (!item) return;
            const code = item.getAttribute('data-value');
            const name = item.getAttribute('data-nama');
            const satuan = item.getAttribute('data-satuan') || '-';
            const rawStok = item.getAttribute('data-stok') || '0';
            const stok = parseLocaleFloat(rawStok);

            comboboxInput.value = `${code} - ${name}`;
            hiddenProductInput.value = code;

            satuanInput.value = satuan;
            stokSistemInput.value = formatQtyJs(stok);
            stokSistemInput.setAttribute('data-raw-stok', stok);

            calculateSelisihPreview();

            comboboxDropdown.classList.add('hidden');
            comboboxArrow.classList.remove('rotate-180');
        }

        // Deteksi ketikan user (Live Search & exact match)
        comboboxInput.addEventListener('input', function() {
            filterCombobox(this.value);
        });

        comboboxInput.addEventListener('change', function() {
            const val = this.value.trim().toLowerCase();
            if (!val) return;
            let matched = null;
            comboboxItems.forEach(item => {
                const code = (item.getAttribute('data-value') || '').toLowerCase();
                const name = (item.getAttribute('data-nama') || '').toLowerCase();
                const fullStr = `${code} - ${name}`.toLowerCase();
                if (code === val || fullStr === val || name === val) {
                    matched = item;
                }
            });
            if (matched) {
                selectComboboxItem(matched);
            }
        });

        // Pilih item dari list dropdown (click & mousedown)
        comboboxItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                selectComboboxItem(this);
            });
            item.addEventListener('mousedown', function(e) {
                e.preventDefault();
                selectComboboxItem(this);
            });
        });

        // Tutup dropdown jika user klik di luar area combobox
        document.addEventListener('click', function(e) {
            if (comboboxContainer && !comboboxContainer.contains(e.target)) {
                comboboxDropdown.classList.add('hidden');
                comboboxArrow.classList.remove('rotate-180');
            }
        });
    }

    // Fungsi pencarian/filter item
    function filterCombobox(query) {
        const cleanQuery = query.toLowerCase().trim();
        let matchCount = 0;

        comboboxItems.forEach(item => {
            const code = item.getAttribute('data-value').toLowerCase();
            const name = item.getAttribute('data-nama').toLowerCase();

            if (code.includes(cleanQuery) || name.includes(cleanQuery)) {
                item.classList.remove('hidden');
                matchCount++;
            } else {
                item.classList.add('hidden');
            }
        });

        if (matchCount === 0) {
            comboboxEmpty.classList.remove('hidden');
        } else {
            comboboxEmpty.classList.add('hidden');
        }
    }

    // Fungsi reset form combobox total
    function resetComboboxForm() {
        if (adjustmentForm) adjustmentForm.reset();
        if (hiddenProductInput) hiddenProductInput.value = '';
        if (comboboxInput) comboboxInput.value = '';
        if (satuanInput) satuanInput.value = '-';
        if (stokSistemInput) {
            stokSistemInput.value = '0';
            stokSistemInput.removeAttribute('data-raw-stok');
        }
        calculateSelisihPreview();
    }

    function parseLocaleFloat(val) {
        if (val === null || val === undefined || val === '') return 0;
        const clean = String(val).replace(',', '.');
        const num = parseFloat(clean);
        return isNaN(num) ? 0 : num;
    }

    function formatQtyJs(val) {
        const num = parseLocaleFloat(val);
        const abs = Math.abs(num);
        const sign = num < 0 ? '-' : '';
        
        if (Math.floor(abs) === abs) {
            return sign + Math.floor(abs).toString();
        }
        
        let str = abs.toFixed(3);
        str = str.replace(/\.?0+$/, '');
        return sign + str.replace('.', ',');
    }

    // --- FORM LOGIC & LIVE PREVIEW ---
    function calculateSelisihPreview() {
        if (!stokSistemInput || !stokFisikInput) return;

        const stokSistemRaw = stokSistemInput.getAttribute('data-raw-stok');
        const stokSistem = (stokSistemRaw !== null && stokSistemRaw !== '') 
            ? parseLocaleFloat(stokSistemRaw) 
            : parseLocaleFloat(stokSistemInput.value || '0');

        const stokFisik = parseLocaleFloat(stokFisikInput.value || '0');
        const selisih = stokFisik - stokSistem;
        const prefix = selisih > 0 ? '+' : '';
        
        previewText.textContent = prefix + formatQtyJs(selisih);
        resetPreviewClass();

        if (selisih < 0) {
            previewBox.className = 'w-full bg-error/10 border border-error/20 rounded-lg px-md py-2.5 flex items-center gap-2 transition-all';
            previewText.className = 'font-body-md font-bold text-error';
            previewLabel.textContent = 'Shrinkage (Penyusutan/Hilang)';
            previewLabel.className = 'text-xs text-error font-medium uppercase';
        } else if (selisih > 0) {
            previewBox.className = 'w-full bg-primary/10 border border-primary/20 rounded-lg px-md py-2.5 flex items-center gap-2 transition-all';
            previewText.className = 'font-body-md font-bold text-primary';
            previewLabel.textContent = 'Surplus (Berlebih)';
            previewLabel.className = 'text-xs text-primary font-medium uppercase';
        } else {
            previewLabel.textContent = 'Balanced';
        }
    }

    // --- MODAL KONFIRMASI (SUBMIT) ---
    function showConfirmAdjustmentModal() {
        const selectedCode = hiddenProductInput.value;
        let productName = '-';

        // Cari nama produk berdasarkan kode_barang yang aktif di list item combobox
        comboboxItems.forEach(item => {
            if (item.getAttribute('data-value') === selectedCode) {
                productName = item.getAttribute('data-nama');
            }
        });
        
        document.getElementById('confirmProductName').textContent = productName;
        document.getElementById('confirmSelisihValue').textContent = previewText.textContent;
        document.getElementById('confirmSelisihValue').className = previewText.className;
        document.getElementById('confirmAdjustmentText').textContent = 'Apakah Anda yakin ingin menyimpan draft adjustment ini?';
        
        confirmAdjustmentModal.classList.remove('hidden');
        confirmAdjustmentModal.classList.add('flex');
    }

    function hideConfirmAdjustmentModal() {
        confirmAdjustmentModal.classList.add('hidden');
        confirmAdjustmentModal.classList.remove('flex');
    }

    // --- MODAL READ-ONLY DETAIL ---
    function showReadOnlyDetail(payload) {
        const modal = document.getElementById('detailAdjustmentModal');
        const container = document.getElementById('detailAdjustmentContent');
        if (!modal || !container) return;

        function safeFloat(val) {
            if (val === null || val === undefined || val === '') return 0;
            const clean = String(val).trim().replace(',', '.');
            const num = parseFloat(clean);
            return isNaN(num) ? 0 : num;
        }

        function safeFormat(val) {
            const num = safeFloat(val);
            const abs = Math.abs(num);
            const sign = num < 0 ? '-' : '';
            if (Math.floor(abs) === abs) {
                return sign + Math.floor(abs).toString();
            }
            let str = abs.toFixed(3).replace(/\.?0+$/, '');
            return sign + str.replace('.', ',');
        }

        const stokSistemFmt = safeFormat(payload.stok_sistem);
        const stokFisikFmt = safeFormat(payload.stok_fisik);
        const selisihNum = safeFloat(payload.selisih);
        const selisihPrefix = selisihNum > 0 ? '+' : '';
        const selisihFmt = selisihPrefix + safeFormat(selisihNum);

        const rows = [
            ['Tanggal Adjustment', payload.tanggal || '-'],
            ['Nama Barang', payload.barang || '-'],
            ['Satuan (UoM)', payload.satuan || '-'],
            ['Stok Sistem', stokSistemFmt],
            ['Stok Fisik Gudang', stokFisikFmt],
            ['Selisih Audit', selisihFmt],
            ['Staf Gudang (Input)', payload.staf_gudang || '-'],
            ['Verifikasi (Supervisor)', payload.diverifikasi_oleh || '-'],
            ['Status Sync', payload.status || '-'],
            ['Alasan / Keterangan', payload.keterangan || '-']
        ];
        
        container.innerHTML = '';
        const wrapper = document.createElement('div');
        wrapper.className = 'w-full space-y-2.5';

        rows.forEach(function (row) {
            const card = document.createElement('div');
            card.className = 'flex flex-col sm:flex-row sm:items-center sm:justify-between rounded-xl border border-outline-variant/40 bg-surface-container-low p-3 gap-1';
            
            const labelEl = document.createElement('span');
            labelEl.className = 'text-xs font-bold uppercase tracking-wider text-on-surface-variant';
            labelEl.textContent = row[0];
            
            const valueEl = document.createElement('span');
            valueEl.className = 'font-bold text-on-surface text-sm break-words';
            if (row[0] === 'Selisih Audit') {
                valueEl.className += selisihNum < 0 ? ' text-error font-black' : (selisihNum > 0 ? ' text-primary font-black' : ' text-on-surface');
            }
            valueEl.textContent = row[1];
            
            card.appendChild(labelEl);
            card.appendChild(valueEl);
            wrapper.appendChild(card);
        });

        container.appendChild(wrapper);
        
        modal.classList.remove('hidden', 'opacity-0', 'pointer-events-none');
        modal.classList.add('flex');
        modal.removeAttribute('aria-hidden');
        modal.removeAttribute('inert');
        modal.style.removeProperty('pointer-events');

        if (window.TB39Interaction && typeof window.TB39Interaction.refresh === 'function') {
            window.TB39Interaction.refresh();
        }
        if (window.TB39UiStability && typeof window.TB39UiStability.refresh === 'function') {
            window.TB39UiStability.refresh();
        }
    }

    function hideDetailAdjustmentModal() {
        const modal = document.getElementById('detailAdjustmentModal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');

        if (window.TB39Interaction && typeof window.TB39Interaction.refresh === 'function') {
            window.TB39Interaction.refresh();
        }
        if (window.TB39UiStability && typeof window.TB39UiStability.refresh === 'function') {
            window.TB39UiStability.refresh();
        }
    }

    window.showReadOnlyDetail = showReadOnlyDetail;
    window.hideDetailAdjustmentModal = hideDetailAdjustmentModal;

    // --- EVENT LISTENERS ---
    if (stokFisikInput) {
        stokFisikInput.addEventListener('input', calculateSelisihPreview);
    }

    if (adjustmentForm) {
        adjustmentForm.addEventListener('submit', function (event) {
            if (allowAdjustmentSubmit) {
                return true;
            }
            event.preventDefault();
            
            // Validasi data combobox (apakah kode_barang sudah terisi)
            if (!hiddenProductInput.value || !comboboxInput.value) {
                alert('Produk wajib dicari dan dipilih terlebih dahulu.');
                return false;
            }
            if (!stokFisikInput.value) {
                alert('Stok fisik wajib diisi.');
                return false;
            }
            const keterangan = document.getElementById('keterangan').value.trim();
            if (keterangan.length < 5) {
                alert('Keterangan minimal 5 karakter.');
                return false;
            }
            
            showConfirmAdjustmentModal();
            return false;
        });
    }

    if (executeAdjustmentSubmitBtn) {
        executeAdjustmentSubmitBtn.addEventListener('click', function () {
            allowAdjustmentSubmit = true;
            hideConfirmAdjustmentModal();
            adjustmentForm.submit();
        });
    }

    // --- DEBOUNCE SEARCH LOG DATA ---
    const searchInput = document.getElementById('globalSearch');
    if (searchInput) {
        let timer = null;
        searchInput.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                searchInput.form.submit();
            }, 500);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const openButton = document.getElementById('openAdjustmentModalBtn');
        const modal = document.getElementById('adjustmentModal');

        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
        }

        openButton?.addEventListener('click', function (event) {
            event.preventDefault();
            window.openAdjustmentModal();
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        const modal = document.getElementById('adjustmentModal');

        if (modal && !modal.classList.contains('hidden')) {
            window.closeAdjustmentModal();
        }
    });

</script>
@endpush