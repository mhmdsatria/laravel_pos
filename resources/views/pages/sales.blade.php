@extends('layouts.app')

@section('title', 'Transaksi Penjualan | Toko Bangunan 39')

@section('content')
@php
    $salesRefundColumnReady = \Illuminate\Support\Facades\Schema::hasColumn('tbl_penjualan', 'refund_at');
@endphp
<div class="mb-lg flex flex-col gap-md lg:flex-row lg:items-center lg:justify-between">
    <div>
        <h2 class="uppercase font-headline-xl text-headline-xl text-on-surface">
            Riwayat Penjualan
        </h2>

        <p class="mt-1 text-on-surface-variant">
            Kelola transaksi material harian, harga segmen, pembayaran, dan stok secara terintegrasi.
        </p>
    </div>

    <button type="button"
        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-primary px-md py-2 font-bold text-on-primary shadow-md transition-all hover:brightness-110 active:scale-95"
        onclick="openPosModal()">
        <span class="material-symbols-outlined">add</span>
        <span>Transaksi Baru</span>
    </button>
</div>

<div class="overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest shadow-sm">
    {{-- Search dan filter tanggal --}}
    <div class="border-b border-outline-variant bg-surface-container-lowest px-lg py-md">
        <div class="flex flex-col gap-md 2xl:flex-row 2xl:items-end 2xl:justify-between">
            <form method="GET" action="{{ route('sales.index') }}"
                class="grid w-full grid-cols-1 gap-sm md:grid-cols-2 xl:grid-cols-[minmax(320px,440px)_170px_170px_auto_auto] xl:items-end">
                {{-- Search --}}
                <div class="min-w-0 md:col-span-2 xl:col-span-1">


                    <div class="relative w-full">
                        <span
                            class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 z-10 -translate-y-1/2 text-on-surface-variant"
                            style="font-size: 20px; line-height: 20px;">
                            search
                        </span>

                        <input id="sales-search" name="search" value="{{ $search }}"
                            placeholder="Cari invoice, customer, atau kasir..." type="text" autocomplete="off"
                            class="block h-11 w-full border border-outline-variant bg-surface-container-low pl-12 pr-4 text-body-md text-on-surface outline-none transition-all placeholder:text-on-surface-variant/60 focus:border-primary focus:ring-4 focus:ring-primary/10"
                            style="border-radius: 9999px;">
                    </div>
                </div>

                {{-- Tanggal mulai --}}
                <div>

                    <input id="sales-date-from" name="date_from" value="{{ $dateFrom }}" type="date"
                        class="block h-11 w-full rounded-xl border border-outline-variant bg-surface px-3 text-body-md text-on-surface outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                </div>

                {{-- Tanggal akhir --}}
                <div>

                    <input id="sales-date-to" name="date_to" value="{{ $dateTo }}" type="date"
                        class="block h-11 w-full rounded-xl border border-outline-variant bg-surface px-3 text-body-md text-on-surface outline-none focus:border-primary focus:ring-4 focus:ring-primary/10">
                </div>

                <button type="submit"
                    class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-primary px-lg font-bold text-on-primary transition-opacity hover:opacity-90">
                    <span class="material-symbols-outlined text-[19px]">filter_alt</span>
                    Terapkan
                </button>

                <!-- <a
                    href="{{ route('sales.index') }}"
                    class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-outline-variant bg-surface px-lg font-bold text-on-surface-variant transition-colors hover:bg-surface-container-high"
                >
                    <span class="material-symbols-outlined text-[19px]">today</span>
                    Hari Ini
                </a> -->
            </form>

            <!-- <div class="shrink-0 rounded-xl bg-surface-container-low px-md py-sm">
                <p class="text-label-sm uppercase text-on-surface-variant">
                    Periode
                </p>

                <p class="font-bold text-on-surface">
                    @if ($dateFrom === $dateTo)
                        {{ \Illuminate\Support\Carbon::parse($dateFrom)->format('d M Y') }}
                    @else
                        {{ \Illuminate\Support\Carbon::parse($dateFrom)->format('d M Y') }}
                        sampai
                        {{ \Illuminate\Support\Carbon::parse($dateTo)->format('d M Y') }}
                    @endif
                </p>

                <p class="mt-1 text-label-md text-on-surface-variant">
                    Total nota: {{ $salesHistory->total() }}
                </p>
            </div> -->
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-left" id="historyTable">
            <thead>
                <tr class="bg-surface-container-low">
                    <th class="px-lg py-3 text-label-md font-label-md uppercase text-on-surface-variant">No</th>
                    <th class="px-lg py-3 text-label-md font-label-md uppercase text-on-surface-variant">Invoice No</th>
                    <th class="px-lg py-3 text-label-md font-label-md uppercase text-on-surface-variant">Date</th>
                    <th class="px-lg py-3 text-label-md font-label-md uppercase text-on-surface-variant">Customer</th>
                    <th class="px-lg py-3 text-center text-label-md font-label-md uppercase text-on-surface-variant">
                        Type</th>
                    <th class="px-lg py-3 text-right text-label-md font-label-md uppercase text-on-surface-variant">
                        Total Amount</th>
                    <th class="px-lg py-3 text-label-md font-label-md uppercase text-on-surface-variant">Status</th>
                    <th class="px-lg py-3 text-center text-label-md font-label-md uppercase text-on-surface-variant">
                        Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-outline-variant">
                @forelse ($salesHistory as $key => $history)
                @php
                    $isHistoryRefunded = $salesRefundColumnReady && ! blank($history->refund_at);
                @endphp
                <tr class="group transition-colors hover:bg-surface-container-low {{ $isHistoryRefunded ? 'opacity-75' : '' }}">
                    <td class="px-lg py-4 text-body-md">
                        {{ $salesHistory->firstItem() + $key }}
                    </td>

                    <td class="px-lg py-4 text-body-md font-bold text-primary">
                        {{ $history->no_invoice }}
                    </td>

                    <td class="px-lg py-4 text-body-md">
                        {{ optional($history->tgl_transaksi)->format('d M Y H:i') }}
                    </td>

                    <td class="px-lg py-4 text-body-md font-medium">
                        {{ $history->nama_pelanggan }}
                    </td>

                    <td class="px-lg py-4 text-center">
                        <span
                            class="rounded-full bg-surface-container-high px-2 py-0.5 text-[10px] font-bold uppercase text-on-surface-variant">
                            {{ $history->tipe_pelanggan }}
                        </span>
                    </td>

                    <td class="px-lg py-4 text-right text-body-md font-bold">
                        Rp {{ number_format((int) $history->total_belanja, 0, ',', '.') }}
                    </td>

                    <td class="px-lg py-4">
                        @if ($isHistoryRefunded)
                            <span class="rounded-lg bg-error-container px-3 py-1 text-[11px] font-bold uppercase text-error">
                                Refund
                            </span>
                        @elseif ($history->status === 'Lunas')
                            <span class="rounded-lg bg-primary-container/20 px-3 py-1 text-[11px] font-bold uppercase text-primary">
                                Lunas
                            </span>
                        @else
                            <span class="rounded-lg bg-error-container px-3 py-1 text-[11px] font-bold uppercase text-error">
                                Tempo
                            </span>
                        @endif
                    </td>

                    <td class="px-lg py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('sales.show', $history->id) }}"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-on-surface-variant transition-colors hover:bg-primary/10 hover:text-primary"
                                title="Lihat detail transaksi">
                                <span class="material-symbols-outlined text-[20px]">
                                    visibility
                                </span>
                            </a>

                            <a href="{{ route('sales.print', ['id' => $history->id, 'auto_print' => 1]) }}" target="_blank" rel="noopener" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-on-surface-variant transition-colors hover:bg-primary/10 hover:text-primary" title="Cetak nota">
                                <span class="material-symbols-outlined text-[20px]">receipt</span>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-lg py-xl text-center text-on-surface-variant">
                        Tidak ada transaksi pada periode yang dipilih.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="border-t border-outline-variant bg-surface-container-lowest px-lg py-md">
        {{ $salesHistory->links() }}
    </div>
</div>
@endsection


@push('modals')
<div class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden" id="pos-modal">
    <div
        class="glass-card w-full max-w-5xl h-[90vh] rounded-2xl overflow-hidden border border-outline-variant flex flex-col animate-in zoom-in duration-300">
        <div
            class="px-lg py-md border-b border-outline-variant flex justify-between items-center bg-surface-container-lowest">
            <div class="flex items-center gap-3 text-primary">
                <!-- <span class="material-symbols-outlined text-3xl font-bold">add_shopping_cart</span>
                <h3 class="font-headline-lg text-headline-lg font-bold">Input Transaksi Penjualan</h3> -->
            </div>
            <button
                class="w-10 h-10 flex items-center justify-center hover:bg-error/10 hover:text-error transition-colors rounded-full"
                type="button" onclick="closePosModal()">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form id="posForm" method="POST" action="{{ route('sales.store') }}" class="flex-1 flex overflow-hidden">
            @csrf
            <div id="pos-sidebar"
                class="w-1/3 border-r border-outline-variant p-lg space-y-md bg-surface-container-lowest overflow-y-auto transition-all">
                <div class="space-y-sm">
                    <label class="block font-label-md text-label-md text-on-surface-variant">Tipe Pelanggan</label>
                    <div class="flex p-1 bg-surface-container-high rounded-xl gap-1" id="customer-type-toggle">
                        <input type="hidden" name="tipe_pelanggan" id="tipe_pelanggan_input" value="USER">
                        <button type="button"
                            class="flex-1 py-2 text-xs font-bold rounded-lg transition-all bg-primary text-on-primary"
                            onclick="switchCustomerType('USER', this)">USER</button>
                        <button type="button"
                            class="flex-1 py-2 text-xs font-bold rounded-lg transition-all hover:bg-surface-container-highest text-on-surface-variant"
                            onclick="switchCustomerType('TOKO', this)">TOKO</button>
                        <button type="button"
                            class="flex-1 py-2 text-xs font-bold rounded-lg transition-all hover:bg-surface-container-highest text-on-surface-variant"
                            onclick="switchCustomerType('SALES', this)">SALES</button>
                    </div>
                </div>
                <div class="space-y-md" id="conditional-fields-container">
                    <div class="space-y-sm" id="field-user">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Nama Customer</label>
                        <input
                            class="w-full bg-surface border border-outline-variant rounded-xl px-4 py-2.5 text-body-md focus:ring-2 focus:ring-primary outline-none"
                            name="nama_pelanggan_manual" id="nama_pelanggan_manual"
                            placeholder="Masukkan nama pembeli..." type="text" />
                        <textarea
                            class="mt-sm w-full rounded-xl border border-outline-variant bg-surface px-4 py-2.5 text-body-md outline-none focus:border-primary focus:ring-4 focus:ring-primary/10"
                            name="alamat_pelanggan_manual"
                            id="alamat_pelanggan_manual"
                            placeholder="Alamat pembeli untuk faktur / surat jalan"
                            rows="3"
                        ></textarea>
                    </div>
                    <div class="space-y-sm hidden" id="field-toko">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Mitra Toko</label>
                        <div class="relative w-full dropdown-container-toko">
                            <input type="hidden" name="customer_id_toko" id="customer_id_toko_select">
                            <input 
                                type="text" 
                                id="toko_search_input" 
                                class="w-full bg-surface border border-outline-variant rounded-xl px-4 py-2.5 text-body-md focus:ring-2 focus:ring-primary outline-none" 
                                placeholder="Cari Mitra Toko..." 
                                autocomplete="off"
                            >
                            <div id="toko_dropdown_list" class="dropdown-list hidden custom-scrollbar"></div>
                        </div>
                    </div>
                    <div class="space-y-md hidden" id="field-sales">
                        <div class="space-y-sm">
                            <label class="block font-label-md text-label-md text-on-surface-variant">Target Toko</label>
                            <div class="relative w-full dropdown-container-sales-toko">
                                <input type="hidden" name="customer_id_sales" id="customer_id_sales_select">
                                <input 
                                    type="text" 
                                    id="sales_toko_search_input" 
                                    class="w-full bg-surface border border-outline-variant rounded-xl px-4 py-2.5 text-body-md focus:ring-2 focus:ring-primary outline-none" 
                                    placeholder="Cari Toko Tujuan..." 
                                    autocomplete="off"
                                >
                                <div id="sales_toko_dropdown_list" class="dropdown-list hidden custom-scrollbar"></div>
                            </div>
                        </div>
                        <div class="space-y-sm">
                            <label class="block font-label-md text-label-md text-on-surface-variant">Salesperson</label>
                            <div class="relative w-full dropdown-container-salesperson">
                                <input type="hidden" name="sales_id" id="sales_id_select">
                                <input 
                                    type="text" 
                                    id="salesperson_search_input" 
                                    class="w-full bg-surface border border-outline-variant rounded-xl px-4 py-2.5 text-body-md focus:ring-2 focus:ring-primary outline-none" 
                                    placeholder="Cari Sales..." 
                                    autocomplete="off"
                                >
                                <div id="salesperson_dropdown_list" class="dropdown-list hidden custom-scrollbar"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="border-outline-variant" />
                <div class="space-y-sm">
                    <label class="block font-label-md text-label-md text-on-surface-variant">Metode Pembayaran</label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="hidden" name="metode_bayar" id="metode_bayar_input" value="CASH">
                        <button type="button"
                            class="py-2.5 rounded-xl border-2 border-primary bg-primary-container/20 text-primary font-bold text-sm"
                            id="pay-cash" onclick="switchPayMethod('CASH')">CASH</button>
                        <button type="button"
                            class="py-2.5 rounded-xl border border-outline-variant text-on-surface-variant font-bold text-sm"
                            id="pay-tempo" onclick="switchPayMethod('TEMPO')">TEMPO</button>
                    </div>
                </div>
                <div class="space-y-sm hidden" id="due-date-container">
                    <label class="block font-label-md text-label-md text-on-surface-variant">Jatuh Tempo</label>
                    <input
                        class="w-full bg-surface border border-outline-variant rounded-xl px-4 py-2.5 text-body-md focus:ring-2 focus:ring-primary outline-none"
                        name="jatuh_tempo" id="jatuh_tempo_input" type="date" />
                </div>
                <div class="mt-4 border-t border-outline-variant/50 pt-4">    
                    <button
                            type="button"
                            id="btn-tambah-barang"
                            onclick="openProductSelectionModal()"
                            class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-outline-variant bg-surface-container-lowest py-3 font-bold text-on-surface-variant transition-all hover:border-primary hover:text-primary"
                        >
                            <span class="material-symbols-outlined">add_box</span>
                            <span>Pilih Produk dari Katalog</span>
                        </button>
                    </div>
            </div>

            <div class="flex-1 p-lg flex flex-col bg-surface-container-low overflow-hidden">
                <div class="flex items-center gap-2 mb-3">
                    <button type="button" onclick="toggleSidebar()"
                        class="p-2 rounded-lg bg-surface-container-highest hover:bg-primary hover:text-on-primary text-on-surface-variant transition-all flex items-center justify-center shadow-sm"
                        title="Sembunyikan/Tampilkan Pelanggan">
                        <span class="material-symbols-outlined text-lg" id="sidebar-toggle-icon">chevron_left</span>
                    </button>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">
                            Daftar Keranjang Belanja
                        </p>
                        <p class="text-label-sm text-on-surface-variant">
                            Kuantitas diatur melalui menu Pilih Produk dari Katalog.
                        </p>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto flex flex-col justify-between">
                    <div>
                        <div class="overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest">
                            <table class="w-full border-collapse text-left">
                                <thead class="bg-surface-container-low">
                                    <tr class="text-label-md font-bold uppercase tracking-wide text-on-surface-variant">
                                        <th class="w-12 px-md py-3 text-center">No</th>
                                        <th class="px-md py-3">Nama Barang</th>
                                        <th class="w-20 px-md py-3 text-center">Qty</th>
                                        <th class="w-24 px-md py-3 text-center">Satuan</th>
                                        <th class="w-36 px-md py-3 text-right">Harga Jual</th>
                                        <th class="w-40 px-md py-3 text-right">Subtotal</th>
                                        <th class="w-16 px-md py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="cart-rows" class="divide-y divide-outline-variant/60"></tbody>
                            </table>
                        </div>
                    </div>

                    
                </div>
                <div class="mt-lg border-t border-outline-variant pt-lg flex items-center justify-between">
                    <div class="space-y-0.5">
                        <p class="text-on-surface-variant text-xs font-semibold uppercase tracking-wider">Grand Total
                        </p>
                        <h4 class="text-2xl font-black text-primary leading-none" id="grand-total">Rp 0</h4>
                        <label
                            id="save-master-price-wrapper"
                            class=" hidden items-start gap-2 px-3 py-2 text-left text-xs font-semibold text-on-surface-variant"
                        >
                            <input
                                type="checkbox"
                                name="save_price_to_master"
                                id="save_price_to_master"
                                value="1"
                                class=" rounded text-primary focus:ring-primary"
                                disabled
                            >
                            <span>
                                Simpan harga manual ke Master Harga untuk target ini.
                                <span class="block font-normal" id="save-master-price-help">
                                    Aktif untuk kategori TOKO atau SALES setelah target dipilih.
                                </span>
                            </span>
                        </label>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="button"
                            class="px-5 py-2.5 rounded-xl font-bold border border-outline-variant hover:bg-surface-container-high active:scale-95 transition-all text-sm text-on-surface-variant"
                            onclick="closePosModal()">
                            Batal
                        </button>
                        <button type="submit" id="submitPosBtn"
                            class="flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-on-primary shadow-md transition-all hover:brightness-110 active:scale-95">
                            <span class="material-symbols-outlined text-lg">save</span>
                            <span>Simpan</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<div
    id="productSelectionModal"
    class="fixed inset-0 z-[80] hidden items-center justify-center bg-slate-950/45 p-4 backdrop-blur-md md:p-6"
>
    <div class="flex h-[88vh] w-full max-w-7xl flex-col overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-2xl">
        {{-- Header --}}
        <div class="flex shrink-0 items-center justify-between border-b border-outline-variant bg-white px-lg py-md">
            <div>
                <h3 class="font-headline-lg text-headline-lg tracking-tight text-on-surface text-left">
                    Pilih Produk & Atur Kuantitas
                </h3>
                <p class="text-body-md text-on-surface-variant">
                    Cari produk dari katalog, tentukan jumlah, lalu gunakan harga otomatis atau ubah harga manual per item.
                </p>
            </div>

            <button
                type="button"
                onclick="closeProductSelectionModal()"
                class="inline-flex h-10 w-10 items-center justify-center rounded-full text-on-surface-variant transition-colors hover:bg-surface-container-high hover:text-on-surface"
                aria-label="Tutup pemilihan produk"
            >
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        {{-- Body --}}
        <div class="flex min-h-0 flex-1 flex-col overflow-hidden lg:flex-row">
            {{-- Katalog produk --}}
            <section class="flex min-h-0 flex-1 flex-col border-b border-outline-variant bg-white lg:w-2/3 lg:border-b-0 lg:border-r">
                <div class="shrink-0 p-lg">
                    <div class="relative">
                        <span
                            class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 z-10 -translate-y-1/2 text-on-surface-variant"
                            style="font-size: 20px; line-height: 20px;"
                        >
                            search
                        </span>

                        <input
                            id="productCatalogSearch"
                            type="text"
                            autocomplete="off"
                            placeholder="Cari kode produk atau nama barang..."
                            class="block h-12 w-full rounded-xl border border-outline-variant bg-white pl-12 pr-4 text-body-md text-on-surface outline-none transition-all placeholder:text-on-surface-variant/60 focus:border-primary focus:ring-4 focus:ring-primary/10"
                        >
                    </div>
                </div>

                <div class="custom-scrollbar min-h-0 flex-1 overflow-auto px-lg pb-lg">
                    <table class="w-full min-w-[760px] border-collapse text-left">
                        <thead class="sticky top-0 z-20 bg-surface-container-low">
                            <tr class="text-label-md uppercase tracking-wider text-on-surface-variant">
                                <th class="rounded-l-xl px-md py-md">Produk</th>
                                <th class="px-md py-md">Satuan</th>
                                <th class="px-md py-md text-right">Stok</th>
                                <th class="px-md py-md text-right">Harga</th>
                                <th class="rounded-r-xl px-md py-md text-center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody id="productCatalogRows" class="divide-y divide-outline-variant">
                            <tr>
                                <td colspan="5" class="px-md py-xl text-center text-on-surface-variant">
                                    Memuat katalog produk...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Ringkasan pilihan --}}
            <aside class="flex min-h-0 flex-col bg-surface-container-low lg:w-1/3">
                <div class="flex shrink-0 items-center justify-between border-b border-outline-variant bg-surface-container-high/50 px-lg py-md">
                    <h4 class="flex items-center gap-sm font-headline-md text-headline-md text-on-surface">
                        <span
                            class="material-symbols-outlined text-primary"
                            style="font-variation-settings: 'FILL' 1;"
                        >
                            shopping_cart
                        </span>
                        Daftar Pilihan Barang
                    </h4>

                    <span
                        id="selectedProductCount"
                        class="rounded-full bg-primary px-sm py-1 text-label-md font-bold text-on-primary"
                    >
                        0 Item
                    </span>
                </div>

                <div
                    id="selectedProductList"
                    class="custom-scrollbar min-h-0 flex-1 space-y-md overflow-auto p-lg"
                >
                    <div class="flex h-full min-h-52 flex-col items-center justify-center rounded-xl border-2 border-dashed border-outline-variant/60 p-xl text-center text-on-surface-variant">
                        <span class="material-symbols-outlined mb-sm text-4xl">add_shopping_cart</span>
                        <p class="font-bold">Belum ada produk dipilih</p>
                        <p class="mt-1 text-label-sm">Klik tombol Tambah pada katalog produk.</p>
                    </div>
                </div>

                <div class="shrink-0 border-t border-outline-variant bg-white p-lg">
                    <div class="flex items-center justify-between">
                        <span class="text-body-md text-on-surface-variant">Total Estimasi</span>
                        <span id="selectionGrandTotal" class="font-headline-lg text-headline-lg font-bold text-primary">
                            Rp 0
                        </span>
                    </div>
                </div>
            </aside>
        </div>

        {{-- Footer --}}
        <div class="flex shrink-0 flex-col-reverse gap-sm border-t border-outline-variant bg-white px-lg py-md sm:flex-row sm:items-center sm:justify-between">
            <button
                type="button"
                onclick="closeProductSelectionModal()"
                class="inline-flex h-11 items-center justify-center rounded-xl border border-outline-variant px-xl font-bold text-on-surface transition-colors hover:bg-surface-container-high"
            >
                Batal
            </button>

            <button
                type="button"
                onclick="confirmProductSelection()"
                class="inline-flex h-11 items-center justify-center gap-sm rounded-xl bg-primary px-xl font-bold text-on-primary shadow-lg shadow-primary/20 transition-all hover:brightness-110 active:scale-95"
            >
                <span class="material-symbols-outlined">check_circle</span>
                Konfirmasi & Masukkan ke Transaksi
            </button>
        </div>
    </div>
</div>

<div class="fixed inset-0 z-[70] hidden items-center justify-center p-4 backdrop-blur-xl bg-black/60"
    id="confirmSubmitModal">
    <div class="w-full max-w-md rounded-2xl bg-white border border-outline-variant shadow-2xl p-lg">
        <div class="flex items-center gap-sm text-primary mb-md">
            <span class="material-symbols-outlined text-3xl">verified</span>
            <h3 class="font-headline-md text-headline-md">Konfirmasi Transaksi</h3>
        </div>
        <p class="text-on-surface-variant mb-lg" id="confirmSummaryText">Pastikan data nota sudah benar.</p>
        <div class="flex justify-end gap-sm">
            <button type="button" class="px-lg py-2 rounded-xl border border-outline-variant font-bold"
                onclick="hideConfirmSubmitModal()">Periksa Lagi</button>
            <button
    type="button"
    id="executeSubmitBtn"
    class="rounded-xl bg-primary px-lg py-2 font-bold text-on-primary"
>
    Ya, Simpan Transaksi
</button>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<style>
.modal-overlay {
    backdrop-filter: blur(12px);
    background-color: rgba(19, 27, 46, 0.4);
}

.glass-card {
    background: rgba(255, 255, 255, 0.98);
    box-shadow: 0 20px 50px -12px rgba(0, 108, 73, 0.2);
}

.custom-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: #bbcabf #f2f3ff;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 7px;
    height: 7px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f2f3ff;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #bbcabf;
    border-radius: 9999px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #6c7a71;
}

.dropdown-list {
    position: absolute;
    left: 0;
    right: 0;
    top: 100%;
    z-index: 90;
    max-height: 220px;
    overflow-y: auto;
    background: #ffffff;
    border: 1px solid #c4c5d5;
    border-radius: 0.75rem;
    margin-top: 4px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15);
}
</style>

<script>
const searchProductsUrl = @json(route('sales.search_products'));
const getPriceUrl = @json(route('sales.get_price'));

let cartIndex = 0;
let searchTimer = null;
let allowFinalSubmit = false;
let latestCatalogProducts = [];

function rupiah(value) {
    return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
}

function parseCurrency(value) {
    const digits = String(value ?? '').replace(/[^0-9]/g, '');
    return digits === '' ? 0 : Number(digits);
}

function formatCurrencyInput(value) {
    const numericValue = parseCurrency(value);
    return numericValue > 0 ? numericValue.toLocaleString('id-ID') : '';
}

function isManualPriceRow(row) {
    return String(row?.dataset?.manualPrice || '0') === '1';
}

function setRowPrice(row, price, sourceText = null) {
    const normalizedPrice = Math.max(0, parseCurrency(price));
    const hiddenInput = row.querySelector('.cart-price-input');
    const priceLabel = row.querySelector('.cart-price-label');
    const manualInput = row.querySelector('.cart-price-manual-input');
    const selectedCard = document.querySelector(
        `.selected-product-card[data-code="${CSS.escape(String(row.dataset.kodeBarang))}"]`
    );

    if (hiddenInput) {
        hiddenInput.value = String(normalizedPrice);
    }

    if (priceLabel) {
        priceLabel.textContent = rupiah(normalizedPrice);
    }

    if (manualInput && document.activeElement !== manualInput) {
        manualInput.value = normalizedPrice > 0 ? normalizedPrice.toLocaleString('id-ID') : '';
    }

    if (selectedCard) {
        const selectedPriceLabel = selectedCard.querySelector('.selected-price-label');
        const selectedPriceInput = selectedCard.querySelector('.selected-price-input');

        if (selectedPriceLabel) {
            selectedPriceLabel.textContent = rupiah(normalizedPrice);
        }

        if (selectedPriceInput && document.activeElement !== selectedPriceInput) {
            selectedPriceInput.value = normalizedPrice > 0 ? normalizedPrice.toLocaleString('id-ID') : '';
        }
    }

    if (sourceText !== null) {
        row.dataset.priceSource = sourceText;
    }

    calculateRow(row);
}

function syncPriceModeUi(row) {
    const manualMode = isManualPriceRow(row);
    const autoToggle = row.querySelector('.cart-auto-price-toggle');
    const manualInput = row.querySelector('.cart-price-manual-input');
    const editButton = row.querySelector('.cart-edit-price-button');
    const sourceLabel = row.querySelector('.cart-price-source-label');
    const manualFlag = row.querySelector('.cart-manual-price-flag');

    if (manualFlag) {
        manualFlag.value = manualMode ? '1' : '0';
    }

    if (autoToggle) {
        autoToggle.checked = !manualMode;
    }

    if (manualInput) {
        manualInput.classList.toggle('hidden', !manualMode);
        manualInput.readOnly = !manualMode;
    }

    if (editButton) {
        editButton.classList.toggle('text-primary', manualMode);
        editButton.classList.toggle('text-on-surface-variant', !manualMode);
    }

    if (sourceLabel) {
        sourceLabel.textContent = manualMode ? 'Harga manual transaksi' : (row.dataset.priceSource || 'Harga otomatis');
        sourceLabel.classList.toggle('text-primary', manualMode);
    }
}

async function setRowAutoPriceMode(row, autoMode) {
    row.dataset.manualPrice = autoMode ? '0' : '1';
    syncPriceModeUi(row);

    if (autoMode) {
        await fetchPriceForRow(row);
    } else {
        const currentPrice = parseCurrency(row.querySelector('.cart-price-input')?.value || 0);
        setRowPrice(row, currentPrice, 'Harga manual transaksi');
        row.querySelector('.cart-price-manual-input')?.focus();
    }

    renderSelectedProducts();
}

function bindRowPriceControls(row) {
    const autoToggle = row.querySelector('.cart-auto-price-toggle');
    const editButton = row.querySelector('.cart-edit-price-button');
    const manualInput = row.querySelector('.cart-price-manual-input');

    if (autoToggle) {
        autoToggle.addEventListener('change', function() {
            setRowAutoPriceMode(row, autoToggle.checked);
        });
    }

    if (editButton) {
        editButton.addEventListener('click', function() {
            setRowAutoPriceMode(row, false);
        });
    }

    if (manualInput) {
        manualInput.addEventListener('input', function() {
            row.dataset.manualPrice = '1';
            setRowPrice(row, parseCurrency(manualInput.value), 'Harga manual transaksi');
            syncPriceModeUi(row);
        });

        manualInput.addEventListener('blur', function() {
            manualInput.value = formatCurrencyInput(manualInput.value);
        });
    }

    syncPriceModeUi(row);
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, function(character) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[character];
    });
}

function openPosModal() {
    const modal = document.getElementById('pos-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
}

function closePosModal() {
    const modal = document.getElementById('pos-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
}

function openProductSelectionModal() {
    const modal = document.getElementById('productSelectionModal');
    const searchInput = document.getElementById('productCatalogSearch');

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');

    searchInput.value = '';
    renderSelectedProducts();
    searchProducts('');

    window.setTimeout(function() {
        searchInput.focus();
    }, 80);
}

function closeProductSelectionModal() {
    const modal = document.getElementById('productSelectionModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');

    if (document.getElementById('pos-modal').classList.contains('hidden')) {
        document.body.classList.remove('overflow-hidden');
    }
}

function confirmProductSelection() {
    renderSelectedProducts();
    closeProductSelectionModal();
}

function switchCustomerType(type, button) {
    const container = document.getElementById('customer-type-toggle');

    container.querySelectorAll('button').forEach(function(item) {
        item.className =
            'flex-1 rounded-lg py-2 text-xs font-bold text-on-surface-variant transition-all hover:bg-surface-container-highest';
    });

    button.className =
        'flex-1 rounded-lg bg-primary py-2 text-xs font-bold text-on-primary transition-all';

    document.getElementById('tipe_pelanggan_input').value = type;

    document.getElementById('field-user').classList.add('hidden');
    document.getElementById('field-toko').classList.add('hidden');
    document.getElementById('field-sales').classList.add('hidden');

    if (type === 'USER') {
        document.getElementById('field-user').classList.remove('hidden');
    } else if (type === 'TOKO') {
        document.getElementById('field-toko').classList.remove('hidden');
    } else if (type === 'SALES') {
        document.getElementById('field-sales').classList.remove('hidden');
    }

    updateMasterPriceOption();
    refreshCartPrices();
}

function switchPayMethod(method) {
    const cashButton = document.getElementById('pay-cash');
    const tempoButton = document.getElementById('pay-tempo');
    const dueDateContainer = document.getElementById('due-date-container');
    const dueDateInput = document.getElementById('jatuh_tempo_input');

    document.getElementById('metode_bayar_input').value = method;

    const activeClasses =
        'rounded-xl border-2 border-primary bg-primary-container/20 py-2.5 text-sm font-bold text-primary';
    const inactiveClasses =
        'rounded-xl border border-outline-variant py-2.5 text-sm font-bold text-on-surface-variant';

    if (method === 'CASH') {
        cashButton.className = activeClasses;
        tempoButton.className = inactiveClasses;
        dueDateContainer.classList.add('hidden');
        dueDateInput.removeAttribute('required');
        dueDateInput.value = '';
    } else {
        tempoButton.className = activeClasses;
        cashButton.className = inactiveClasses;
        dueDateContainer.classList.remove('hidden');
        dueDateInput.setAttribute('required', 'required');
    }
}

function getActiveTargetId() {
    const type = document.getElementById('tipe_pelanggan_input').value;

    if (type === 'TOKO') {
        return document.getElementById('customer_id_toko_select').value || '';
    }

    if (type === 'SALES') {
        return document.getElementById('sales_id_select').value || '';
    }

    return '';
}

function updateMasterPriceOption() {
    const type = document.getElementById('tipe_pelanggan_input')?.value || 'USER';
    const checkbox = document.getElementById('save_price_to_master');
    const wrapper = document.getElementById('save-master-price-wrapper');
    const help = document.getElementById('save-master-price-help');
    const targetId = getActiveTargetId();
    const canSave = (type === 'TOKO' || type === 'SALES') && String(targetId || '').trim() !== '';

    if (!checkbox || !wrapper) {
        return;
    }

    wrapper.classList.toggle('hidden', type === 'USER');
    wrapper.classList.toggle('flex', type !== 'USER');
    checkbox.disabled = !canSave;

    if (!canSave) {
        checkbox.checked = false;
    }

    if (help) {
        if (type === 'TOKO') {
            help.textContent = canSave
                ? ''
                : '';
        } else if (type === 'SALES') {
            help.textContent = canSave
                ? ''
                : '';
        } else {
            help.textContent = 'Aktif untuk kategori TOKO atau SALES setelah target dipilih.';
        }
    }
}

async function searchProducts(keyword) {
    const catalogRows = document.getElementById('productCatalogRows');
    const cleanKw = String(keyword || '').trim();

    catalogRows.innerHTML = `
        <tr>
            <td colspan="5" class="px-md py-xl text-center text-on-surface-variant">
                Memuat katalog produk...
            </td>
        </tr>
    `;

    try {
        const url = new URL(searchProductsUrl, window.location.origin);
        url.searchParams.set('q', cleanKw);
        const response = await fetch(url.toString(), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload.message || 'Gagal memuat produk.');
        }

        latestCatalogProducts = payload.data || [];
        renderProductCatalog(latestCatalogProducts);
    } catch (error) {
        catalogRows.innerHTML = `
            <tr>
                <td colspan="5" class="px-md py-xl text-center font-bold text-error">
                    ${escapeHtml(error.message || 'Gagal memuat katalog produk.')}
                </td>
            </tr>
        `;
    }
}

function getCartRow(code) {
    return document.querySelector(
        `#cart-rows tr[data-kode-barang="${CSS.escape(String(code))}"]`
    );
}

function renderProductCatalog(products) {
    const catalogRows = document.getElementById('productCatalogRows');

    if (!products.length) {
        catalogRows.innerHTML = `
            <tr>
                <td colspan="5" class="px-md py-xl text-center text-on-surface-variant">
                    Produk tidak ditemukan.
                </td>
            </tr>
        `;
        return;
    }

    catalogRows.innerHTML = products.map(function(product) {
        const row = getCartRow(product.kode_barang);
        const selected = Boolean(row);
        const price = row
            ? Number(row.querySelector('.cart-price-input')?.value || 0)
            : 0;

        return `
            <tr class="group transition-colors hover:bg-surface-container-low">
                <td class="px-md py-md">
                    <div class="font-semibold text-on-surface">
                        ${escapeHtml(product.nama_barang)}
                    </div>
                    <div class="text-label-sm text-on-surface-variant flex flex-wrap items-center gap-1.5 mt-0.5">
                        <span>SKU: ${escapeHtml(product.kode_barang)}</span>
                        <span class="text-[11px] font-bold text-error">
                            HPP: ${rupiah(product.harga_beli_terakhir || 0)}
                        </span>
                    </div>
                </td>

                <td class="px-md py-md">
                    <span class="rounded-lg bg-surface-container-high px-sm py-1 text-label-md font-bold text-on-surface-variant">
                        ${escapeHtml(product.satuan || 'Unit')}
                    </span>
                </td>

                <td class="px-md py-md text-right">
                    <span class="font-semibold ${Number(product.sisa_stok || 0) > 0 ? 'text-primary' : 'text-error'}">
                        ${Number(product.sisa_stok || 0).toLocaleString('id-ID')}
                    </span>
                </td>

                <td class="px-md py-md text-right">
                    <div class="font-semibold text-on-surface">
                        ${price > 0 ? rupiah(price) : 'Sesuai segmen'}
                    </div>
                    <div class="text-label-sm text-on-surface-variant">Otomatis atau manual saat dipilih</div>
                </td>

                <td class="px-md py-md text-center">
                    <button
                        type="button"
                        data-code="${escapeHtml(product.kode_barang)}"
                        class="catalog-add-button mx-auto inline-flex items-center gap-xs rounded-lg border px-md py-sm text-label-md font-bold transition-all ${
                            selected
                                ? 'border-primary bg-primary/10 text-primary'
                                : 'border-outline-variant bg-white text-on-surface hover:border-primary hover:bg-primary hover:text-on-primary'
                        }"
                    >
                        <span class="material-symbols-outlined text-[18px]">
                            ${selected ? 'check' : 'add'}
                        </span>
                        ${selected ? 'Dipilih' : 'Tambah'}
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    catalogRows.querySelectorAll('.catalog-add-button').forEach(function(button) {
        button.addEventListener('click', async function() {
            const code = button.dataset.code;
            const product = latestCatalogProducts.find(function(item) {
                return String(item.kode_barang) === String(code);
            });

            if (!product) {
                return;
            }

            await addRow(product);
            renderProductCatalog(latestCatalogProducts);
            renderSelectedProducts();
        });
    });
}

async function addRow(product) {
    const existingRow = getCartRow(product.kode_barang);

    if (existingRow) {
        const quantityInput = existingRow.querySelector('.cart-qty');
        const currentQuantity = Number(quantityInput.value || 0);
        const stock = Number(existingRow.dataset.stock || product.sisa_stok || 0);

        if (stock > 0 && currentQuantity >= stock) {
            alert('Jumlah barang sudah mencapai stok yang tersedia.');
            return;
        }

        quantityInput.value = String(currentQuantity + 1);
        calculateRow(existingRow);
        renderSelectedProducts();
        return;
    }

    cartIndex += 1;

    const tbody = document.getElementById('cart-rows');
    const row = document.createElement('tr');

    row.className = 'transition-colors hover:bg-surface-container-low';
    row.dataset.kodeBarang = product.kode_barang;
    row.dataset.namaBarang = product.nama_barang;
    row.dataset.satuan = product.satuan || 'Unit';
    row.dataset.stock = Number(product.sisa_stok || 0);
    row.dataset.hargaBeli = Number(product.harga_beli_terakhir || 0);

    row.innerHTML = `
        <td class="cart-no px-md py-3 text-center text-body-md text-on-surface-variant">
            ${cartIndex}
        </td>

        <td class="px-md py-3">
            <input type="hidden" name="kode_barang[]" value="${escapeHtml(product.kode_barang)}">

            <div class="font-semibold text-on-surface">
                ${escapeHtml(product.nama_barang)}
            </div>

            <div class="text-label-sm text-on-surface-variant flex flex-wrap items-center gap-1.5 mt-0.5">
                <span>${escapeHtml(product.kode_barang)} · Stok <span class="row-stock">${Number(product.sisa_stok || 0)}</span></span>
                <span class="text-[11px] font-bold text-error">
                    HPP: ${rupiah(product.harga_beli_terakhir || 0)}
                </span>
            </div>
        </td>

        <td class="px-md py-3 text-center">
            <input
                type="hidden"
                name="qty[]"
                value="1"
                class="cart-qty"
            >
            <span class="cart-qty-label font-semibold text-on-surface">1</span>
        </td>

        <td class="row-unit px-md py-3 text-center text-body-md text-on-surface-variant">
            ${escapeHtml(product.satuan || 'Unit')}
        </td>

        <td class="px-md py-3 text-right">
            <input type="hidden" name="harga_jual[]" class="cart-price-input" value="0">
            <input type="hidden" name="is_manual_price[]" class="cart-manual-price-flag" value="0">
            <div class="flex flex-col items-end gap-1">
                <div class="flex items-center justify-end gap-2">
                    <span class="cart-price-label font-semibold text-primary">Rp 0</span>
                    <button
                        type="button"
                        class="cart-edit-price-button inline-flex h-7 w-7 items-center justify-center rounded-lg text-on-surface-variant transition-colors hover:bg-primary/10 hover:text-primary"
                        title="Edit harga transaksi"
                    >
                        <span class="material-symbols-outlined text-[18px]">edit</span>
                    </button>
                </div>
                <label class="flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide text-on-surface-variant">
                    <input type="checkbox" class="cart-auto-price-toggle h-3.5 w-3.5 rounded border-outline-variant text-primary" checked>
                    Otomatis
                </label>
                <input
                    type="text"
                    inputmode="numeric"
                    class="cart-price-manual-input hidden h-9 w-32 rounded-lg border border-outline-variant bg-white px-3 text-right text-body-md font-semibold text-on-surface outline-none focus:border-primary focus:ring-4 focus:ring-primary/10"
                    placeholder="0"
                >
                <div class="cart-price-source-label text-[10px] text-on-surface-variant">Harga otomatis</div>
            </div>
        </td>

        <td class="px-md py-3 text-right">
            <input type="hidden" name="subtotal[]" class="cart-subtotal-input" value="0">
            <span class="cart-subtotal-label font-bold text-on-surface">Rp 0</span>
        </td>

        <td class="px-md py-3 text-center">
            <button
                type="button"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-error transition-colors hover:bg-error/10"
                aria-label="Hapus barang"
                title="Hapus barang"
            >
                <span class="material-symbols-outlined text-[19px]">delete</span>
            </button>
        </td>
    `;

    tbody.appendChild(row);
    bindRowPriceControls(row);

    row.querySelector('button[aria-label="Hapus barang"]').addEventListener('click', function() {
        removeCartRow(this);
        renderProductCatalog(latestCatalogProducts);
        renderSelectedProducts();
    });

    await fetchPriceForRow(row);
    calculateRow(row);
    renumberCartRows();
    renderSelectedProducts();
}

function removeCartRow(button) {
    button.closest('tr')?.remove();
    renumberCartRows();
    calculateGrandTotal();
    renderSelectedProducts();
}

function renumberCartRows() {
    document.querySelectorAll('#cart-rows tr').forEach(function(row, index) {
        const numberCell = row.querySelector('.cart-no');

        if (numberCell) {
            numberCell.textContent = String(index + 1);
        }
    });

    cartIndex = document.querySelectorAll('#cart-rows tr').length;
}

async function fetchPriceForRow(row) {
    if (isManualPriceRow(row)) {
        calculateRow(row);
        return;
    }

    const code = row.dataset.kodeBarang;
    const type = document.getElementById('tipe_pelanggan_input').value;
    const targetId = getActiveTargetId();
    const url = new URL(getPriceUrl, window.location.origin);

    url.searchParams.set('kode_barang', code);
    url.searchParams.set('tipe_pelanggan', type);

    if (targetId) {
        url.searchParams.set('target_id', targetId);
    }

    try {
        const response = await fetch(url.toString(), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Harga barang tidak ditemukan.');
        }

        const sourceText = payload.sumber_harga
            ? String(payload.sumber_harga).replace(/_/g, ' ')
            : 'Harga otomatis';

        setRowPrice(row, Number(payload.harga_jual || 0), sourceText);
        row.querySelector('.row-stock').textContent = Number(payload.sisa_stok || 0);
        row.querySelector('.row-unit').textContent = payload.satuan || '-';

        row.dataset.stock = Number(payload.sisa_stok || 0);
        row.dataset.satuan = payload.satuan || '-';
        row.dataset.priceSource = sourceText;
        syncPriceModeUi(row);

        const quantityInput = row.querySelector('.cart-qty');
        quantityInput.max = String(Number(payload.sisa_stok || 0));

        calculateRow(row);
    } catch (error) {
        setRowPrice(row, 0, 'Harga otomatis gagal');
        alert(error.message || 'Gagal mengambil harga barang.');
    }
}
async function refreshCartPrices() {
    const rows = document.querySelectorAll('#cart-rows tr');

    for (const row of rows) {
        if (!isManualPriceRow(row)) {
            await fetchPriceForRow(row);
        } else {
            calculateRow(row);
        }
    }

    calculateGrandTotal();
    renderSelectedProducts();
    renderProductCatalog(latestCatalogProducts);
}
function calculateRow(row) {
    const quantity = Number(row.querySelector('.cart-qty')?.value || 0);
    const price = Number(row.querySelector('.cart-price-input')?.value || 0);
    const subtotal = Math.round(quantity * price);

    row.querySelector('.cart-qty-label').textContent =
        quantity.toLocaleString('id-ID', { maximumFractionDigits: 3 });

    row.querySelector('.cart-subtotal-input').value = subtotal;
    row.querySelector('.cart-subtotal-label').textContent = rupiah(subtotal);

    const selectedCard = document.querySelector(
        `.selected-product-card[data-code="${CSS.escape(String(row.dataset.kodeBarang))}"]`
    );

    if (selectedCard) {
        const selectedSubtotal = selectedCard.querySelector('.selected-subtotal');

        if (selectedSubtotal) {
            selectedSubtotal.textContent = rupiah(subtotal);
        }
    }

    calculateGrandTotal();
}

function calculateGrandTotal() {
    let total = 0;

    document.querySelectorAll('.cart-subtotal-input').forEach(function(input) {
        total += Number(input.value || 0);
    });

    document.getElementById('grand-total').textContent = rupiah(total);

    const modalTotal = document.getElementById('selectionGrandTotal');

    if (modalTotal) {
        modalTotal.textContent = rupiah(total);
    }

    return total;
}

function renderSelectedProducts() {
    const list = document.getElementById('selectedProductList');
    const countBadge = document.getElementById('selectedProductCount');
    const rows = Array.from(document.querySelectorAll('#cart-rows tr'));

    countBadge.textContent = `${rows.length} Item`;

    if (!rows.length) {
        list.innerHTML = `
            <div class="flex h-full min-h-52 flex-col items-center justify-center rounded-xl border-2 border-dashed border-outline-variant/60 p-xl text-center text-on-surface-variant">
                <span class="material-symbols-outlined mb-sm text-4xl">add_shopping_cart</span>
                <p class="font-bold">Belum ada produk dipilih</p>
                <p class="mt-1 text-label-sm">Klik tombol Tambah pada katalog produk.</p>
            </div>
        `;

        calculateGrandTotal();
        return;
    }

    list.innerHTML = rows.map(function(row) {
        const code = row.dataset.kodeBarang;
        const name = row.dataset.namaBarang;
        const unit = row.dataset.satuan || 'Unit';
        const stock = Number(row.dataset.stock || 0);
        const quantity = Number(row.querySelector('.cart-qty')?.value || 1);
        const price = Number(row.querySelector('.cart-price-input')?.value || 0);
        const manualPrice = isManualPriceRow(row);
        const subtotal = Number(row.querySelector('.cart-subtotal-input')?.value || 0);

        return `
            <article
                class="selected-product-card relative flex flex-col gap-md rounded-xl border border-outline-variant bg-white p-md shadow-sm"
                data-code="${escapeHtml(code)}"
            >
                <div class="flex items-start justify-between gap-md">
                    <div class="min-w-0 pr-xl">
                        <h5 class="font-semibold text-on-surface">
                            ${escapeHtml(name)}
                        </h5>
                        <div class="text-label-sm text-on-surface-variant flex flex-wrap items-center gap-1.5 mt-0.5">
                            <span>SKU: ${escapeHtml(code)}</span>
                            <span class="text-[11px] font-bold text-error">
                                HPP: ${rupiah(row?.dataset?.hargaBeli || 0)}
                            </span>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="selected-remove-button absolute right-md top-md text-on-surface-variant transition-colors hover:text-error"
                        data-code="${escapeHtml(code)}"
                        aria-label="Hapus produk"
                    >
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>

                <div class="flex items-end justify-between gap-md">
                    <div class="min-w-0 flex-1">
                        <label class="mb-1 block text-label-md font-bold text-on-surface-variant">
                            Quantity
                        </label>

                        <div class="flex">
                            <input
                                type="number"
                                step="any"
                                min="0.001"
                                max="${stock}"
                                value="${quantity}"
                                data-code="${escapeHtml(code)}"
                                class="selected-qty-input h-10 min-w-0 flex-1 rounded-l-lg border border-outline-variant px-md text-body-md font-semibold text-on-surface outline-none focus:border-primary focus:ring-4 focus:ring-primary/10"
                            >

                            <span class="rounded-r-lg border border-l-0 border-outline-variant bg-surface-container-high px-md py-sm text-label-md font-bold text-on-surface-variant">
                                ${escapeHtml(unit)}
                            </span>
                        </div>

                        <p class="mt-1 text-label-sm text-on-surface-variant">
                            Stok tersedia: ${stock.toLocaleString('id-ID')}
                        </p>
                    </div>

                    <div class="shrink-0 text-right">
                        <div class="text-label-md text-on-surface-variant">Harga Satuan</div>
                        <div class="selected-price-label font-bold text-primary">
                            ${rupiah(price)}
                        </div>
                        <label class="mt-1 flex items-center justify-end gap-1 text-[10px] font-bold uppercase tracking-wide text-on-surface-variant">
                            <input type="checkbox" class="selected-auto-price-toggle h-3.5 w-3.5 rounded border-outline-variant text-primary" data-code="${escapeHtml(code)}" ${manualPrice ? '' : 'checked'}>
                            Otomatis
                        </label>
                        <input
                            type="text"
                            inputmode="numeric"
                            data-code="${escapeHtml(code)}"
                            value="${price > 0 ? price.toLocaleString('id-ID') : ''}"
                            class="selected-price-input mt-1 h-9 w-32 rounded-lg border border-outline-variant px-3 text-right text-body-md font-semibold outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 ${manualPrice ? '' : 'hidden'}"
                            placeholder="0"
                        >
                        <div class="mt-2 text-label-md text-on-surface-variant">Subtotal</div>
                        <div class="selected-subtotal font-headline-md text-headline-md font-bold text-primary">
                            ${rupiah(subtotal)}
                        </div>
                    </div>
                </div>
            </article>
        `;
    }).join('');

    list.querySelectorAll('.selected-qty-input').forEach(function(input) {
        function applyQuantity(normalizeEmpty = false) {
            const row = getCartRow(input.dataset.code);

            if (!row) {
                return;
            }

            if (input.value === '' && !normalizeEmpty) {
                return;
            }

            const stock = Number(row.dataset.stock || 0);
            let valStr = String(input.value || '').replace(',', '.');
            let quantity = Number.parseFloat(valStr);

            if (!Number.isFinite(quantity) || quantity <= 0) {
                if (normalizeEmpty) {
                    quantity = 1;
                } else {
                    return;
                }
            }

            if (stock > 0 && quantity > stock) {
                quantity = stock;
            }

            if (normalizeEmpty) {
                input.value = String(quantity);
            }
            row.querySelector('.cart-qty').value = String(quantity);
            calculateRow(row);
        }

        input.addEventListener('input', function() {
            applyQuantity(false);
        });

        input.addEventListener('change', function() {
            applyQuantity(true);
        });

        input.addEventListener('blur', function() {
            applyQuantity(true);
        });
    });

    list.querySelectorAll('.selected-auto-price-toggle').forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            const row = getCartRow(toggle.dataset.code);

            if (row) {
                setRowAutoPriceMode(row, toggle.checked);
            }
        });
    });

    list.querySelectorAll('.selected-price-input').forEach(function(input) {
        input.addEventListener('input', function() {
            const row = getCartRow(input.dataset.code);

            if (row) {
                row.dataset.manualPrice = '1';
                setRowPrice(row, parseCurrency(input.value), 'Harga manual transaksi');
                syncPriceModeUi(row);
            }
        });

        input.addEventListener('blur', function() {
            input.value = formatCurrencyInput(input.value);
        });
    });

    list.querySelectorAll('.selected-remove-button').forEach(function(button) {
        button.addEventListener('click', function() {
            const row = getCartRow(button.dataset.code);

            if (row) {
                row.remove();
                renumberCartRows();
                calculateGrandTotal();
                renderSelectedProducts();
                renderProductCatalog(latestCatalogProducts);
            }
        });
    });

    calculateGrandTotal();
}

function escapeAttr(value) {
    return escapeHtml(value).replace(/`/g, '&#096;');
}

const posCustomers = @json($customers);
const posSalesPeople = @json($salesPeople);

function initPosSearchableSelects() {
    function setupAutocomplete(containerClass, inputId, hiddenId, listId, data, nameKey, codeKey, emptyText) {
        const input = document.getElementById(inputId);
        const hidden = document.getElementById(hiddenId);
        const list = document.getElementById(listId);
        if (!input || !hidden || !list) return;

        function renderList(query = '') {
            const q = String(query || '').toLowerCase().trim();
            const rawData = Array.isArray(data) ? data : [];
            const selectedName = String(input.dataset.selectedName || '').toLowerCase().trim();
            
            let filtered;
            if (!q || (selectedName && q === selectedName)) {
                filtered = rawData;
            } else {
                filtered = rawData.filter(item => {
                    const name = String(item[nameKey] || '').toLowerCase();
                    const code = String(item[codeKey] || '').toLowerCase();
                    return name.includes(q) || code.includes(q);
                });
            }

            if (!filtered.length) {
                list.innerHTML = `<div class="px-4 py-3 text-xs font-semibold text-on-surface-variant">${emptyText}</div>`;
                list.classList.remove('hidden');
                return;
            }

            list.innerHTML = filtered.map(item => `
                <button type="button" class="block w-full px-4 py-2.5 text-left hover:bg-surface-container-low cursor-pointer border-b border-outline-variant/30 transition-colors" data-id="${item.id}" data-name="${escapeAttr(item[nameKey] || '')}">
                    <div class="font-semibold text-on-surface text-xs">${escapeHtml(item[nameKey] || '')} ${item[codeKey] ? ' - ' + escapeHtml(item[codeKey]) : ''}</div>
                </button>
            `).join('');
            list.classList.remove('hidden');
        }

        input.addEventListener('focus', () => {
            try { input.select(); } catch(e) {}
            renderList('');
        });

        input.addEventListener('click', () => {
            renderList('');
        });

        input.addEventListener('input', (e) => {
            hidden.value = '';
            input.dataset.selectedName = '';
            hidden.dispatchEvent(new Event('change'));
            renderList(e.target.value);
        });

        list.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-id]');
            if (!btn) return;
            hidden.value = btn.dataset.id;
            input.value = btn.dataset.name;
            input.dataset.selectedName = btn.dataset.name;
            list.classList.add('hidden');
            hidden.dispatchEvent(new Event('change'));
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.' + containerClass)) {
                list.classList.add('hidden');
            }
        });
    }

    setupAutocomplete('dropdown-container-toko', 'toko_search_input', 'customer_id_toko_select', 'toko_dropdown_list', posCustomers, 'nama_pelanggan', 'kode_cuts', 'Toko tidak ditemukan');
    setupAutocomplete('dropdown-container-sales-toko', 'sales_toko_search_input', 'customer_id_sales_select', 'sales_toko_dropdown_list', posCustomers, 'nama_pelanggan', 'kode_cuts', 'Toko tidak ditemukan');
    setupAutocomplete('dropdown-container-salesperson', 'salesperson_search_input', 'sales_id_select', 'salesperson_dropdown_list', posSalesPeople, 'nama_sales', 'kode_sales', 'Sales tidak ditemukan');
}

function getCustomerDisplayName() {
    const type = document.getElementById('tipe_pelanggan_input').value;

    if (type === 'USER') {
        return document.getElementById('nama_pelanggan_manual').value || 'User Umum';
    }

    if (type === 'TOKO') {
        return document.getElementById('toko_search_input').value || 'Toko belum dipilih';
    }

    const customerName = document.getElementById('sales_toko_search_input').value || 'Target toko';
    const salesName = document.getElementById('salesperson_search_input').value || 'Sales';

    return customerName + ' / ' + salesName;
}

function showConfirmSubmitModal() {
    const itemCount = document.querySelectorAll('#cart-rows tr').length;
    const total = calculateGrandTotal();
    const payMethod = document.getElementById('metode_bayar_input').value;
    const customerName = getCustomerDisplayName();

    document.getElementById('confirmSummaryText').textContent =
        `Simpan transaksi untuk ${customerName}, jumlah item ${itemCount}, metode bayar ${payMethod}, dengan total ${rupiah(total)}? Setelah disimpan, stok barang akan langsung diperbarui.`;

    const modal = document.getElementById('confirmSubmitModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function hideConfirmSubmitModal() {
    const modal = document.getElementById('confirmSubmitModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function toggleSidebar() {
    const sidebar = document.getElementById('pos-sidebar');
    const icon = document.getElementById('sidebar-toggle-icon');

    sidebar.classList.toggle('hidden');
    icon.textContent = sidebar.classList.contains('hidden')
        ? 'chevron_right'
        : 'chevron_left';
}

document.addEventListener('DOMContentLoaded', function() {
    initPosSearchableSelects();
    const productCatalogSearch = document.getElementById('productCatalogSearch');
    const form = document.getElementById('posForm');
    const executeButton = document.getElementById('executeSubmitBtn');

    productCatalogSearch?.addEventListener('input', function(event) {
        window.clearTimeout(searchTimer);

        searchTimer = window.setTimeout(function() {
            searchProducts(event.target.value);
        }, 250);
    });

    function handlePricingTargetChange() {
        updateMasterPriceOption();
        refreshCartPrices();
    }

    document.getElementById('customer_id_toko_select')
        ?.addEventListener('change', handlePricingTargetChange);

    document.getElementById('customer_id_sales_select')
        ?.addEventListener('change', handlePricingTargetChange);

    document.getElementById('sales_id_select')
        ?.addEventListener('change', handlePricingTargetChange);

    updateMasterPriceOption();

    form?.addEventListener('submit', function(event) {
        if (allowFinalSubmit) {
            return;
        }

        event.preventDefault();

        if (!document.querySelector('#cart-rows tr')) {
            alert('Keranjang belanja masih kosong. Tambahkan minimal satu barang.');
            return;
        }

        showConfirmSubmitModal();
    });

    executeButton?.addEventListener('click', function() {
        allowFinalSubmit = true;
        executeButton.disabled = true;
        executeButton.innerHTML =
            '<span class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>Menyimpan...';

        hideConfirmSubmitModal();
        form.submit();
    });

    document.addEventListener('keydown', function(event) {
        if (event.key !== 'Escape') {
            return;
        }

        const productModal = document.getElementById('productSelectionModal');
        const confirmModal = document.getElementById('confirmSubmitModal');

        if (!confirmModal.classList.contains('hidden')) {
            hideConfirmSubmitModal();
        } else if (!productModal.classList.contains('hidden')) {
            closeProductSelectionModal();
        } else if (!document.getElementById('pos-modal').classList.contains('hidden')) {
            closePosModal();
        }
    });
});
</script>
@endpush
