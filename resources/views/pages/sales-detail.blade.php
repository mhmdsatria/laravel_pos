@extends('layouts.app')

@section('title', 'Detail Penjualan Toko | Toko Bangunan 39')
@section('page_title', 'Detail Penjualan Toko')
@section('active_page', 'sales')

@section('content')
@php
    $customersJson   = $customers->map(fn($c) => ['id' => $c->id, 'nama_pelanggan' => $c->nama_pelanggan, 'alamat_lengkap' => $c->alamat_lengkap ?? ''])->values();
    $salesPeopleJson = $salesPeople->map(fn($s) => ['id' => $s->id, 'kode_sales' => $s->kode_sales, 'nama_sales' => $s->nama_sales])->values();

    $details = collect($sale->details ?? []);
    $refunds = collect($sale->refunds ?? []);
    $refundDetailColumnReady = \Illuminate\Support\Facades\Schema::hasColumn('tbl_penjualan_detail', 'qty_refund');
    $refundOrderColumnReady = \Illuminate\Support\Facades\Schema::hasColumn('tbl_penjualan', 'refund_total');

    $totalQty = (float) $details->sum('qty');
    $totalDelivered = (float) $details->sum('qty_terkirim');
    $totalRefundedQty = $refundDetailColumnReady ? (float) $details->sum('qty_refund') : 0;
    $totalRemaining = max(0, $totalQty - $totalDelivered);
    $refundTotal = $refundOrderColumnReady ? (int) ($sale->refund_total ?? 0) : 0;
    $netTotal = max(0, (int) $sale->total_belanja - $refundTotal);
    $hasAnyRefund = $refundTotal > 0 || $totalRefundedQty > 0;
    $isFullyRefunded = $totalQty > 0 && $totalRefundedQty >= $totalQty;
    $isPartiallyRefunded = $hasAnyRefund && ! $isFullyRefunded;
    $statusLabel = $isFullyRefunded ? 'Refund' : ($isPartiallyRefunded ? 'Refund Sebagian' : ($sale->status ?: '-'));

    $status = strtoupper((string) $statusLabel);

    $statusClass = match ($status) {
        'LUNAS' => 'bg-primary/10 text-primary',
        'TEMPO' => 'bg-tertiary-container text-on-tertiary-container',
        'REFUND' => 'bg-error-container text-error',
        'REFUND SEBAGIAN' => 'bg-secondary/10 text-secondary',
        default => 'bg-surface-container-high text-on-surface-variant',
    };

    $targetId = match ($sale->tipe_pelanggan) {
        'TOKO' => (int) $sale->customer_id,
        'SALES' => (int) $sale->sales_id,
        default => 0,
    };

    $deliveryStatus = match (true) {
        $totalQty <= 0 => 'Belum Ada Barang',
        $totalDelivered <= 0 => 'Belum Dikirim',
        $totalDelivered >= $totalQty => 'Terkirim Semua',
        default => 'Terkirim Sebagian',
    };

    $deliveryBadge = match ($deliveryStatus) {
        'Terkirim Semua' => 'bg-primary/10 text-primary',
        'Terkirim Sebagian' => 'bg-secondary/10 text-secondary',
        default => 'bg-tertiary-container/30 text-on-tertiary-container',
    };
@endphp

<div class="flex flex-col gap-lg">
    {{-- Kembali --}}
    <a
        href="{{ route('sales.index') }}"
        class="inline-flex w-fit items-center gap-1 font-bold uppercase text-primary transition-opacity hover:opacity-80"
    >
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Kembali
    </a>

    {{-- Header dan action bar --}}
    <div class="flex flex-col gap-md md:flex-row md:items-center md:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-sm">
                <h2 class="font-headline-lg text-headline-lg text-on-surface">
                    Detail Penjualan Toko
                </h2>

                <span class="rounded-lg px-sm py-1 text-label-md font-bold uppercase {{ $statusClass }}">
                    {{ $statusLabel }}
                </span>

                <span class="rounded-lg px-sm py-1 text-label-md font-bold uppercase {{ $deliveryBadge }}">
                    {{ $deliveryStatus }}
                </span>
            </div>

            <p class="mt-1 text-label-md text-on-surface-variant">
                {{ optional($sale->tgl_transaksi)->format('d M Y, H:i') ?: '-' }}
            </p>
        </div>

        <div class="flex flex-wrap gap-sm">
            <button
                type="button"
                id="toggle-edit-sale"
                class="inline-flex h-10 items-center gap-2 rounded-lg bg-primary px-md font-bold text-white transition-all hover:brightness-95"
            >
                <span class="material-symbols-outlined text-[18px]">edit_note</span>
                Mode Edit
            </button>

            <a
                href="{{ route('sales.print', ['id' => $sale->id, 'auto_print' => 1]) }}"
                target="_blank"
                rel="noopener"
                class="inline-flex h-10 items-center gap-2 rounded-lg bg-primary px-md font-bold text-white transition-all hover:brightness-95"
            >
                <span class="material-symbols-outlined text-[18px]">receipt</span>
                Cetak Struk
            </a>
<!-- <a
    href="{{ route('sales.invoice', [
        'id' => $sale->id,
        'auto_print' => 1
    ]) }}"
    target="_blank"
    rel="noopener"
> -->
            {{-- Dropdown Print Faktur --}}
            <div class="relative inline-block text-left" id="print-faktur-dropdown">
                <button
                    type="button"
                    id="btn-print-faktur"
                    class="inline-flex h-10 items-center gap-2 rounded-lg bg-primary px-md font-bold text-white transition-all hover:brightness-95 select-none"
                >
                    <span class="material-symbols-outlined text-[18px]">print</span>
                    Print Faktur
                    <span class="material-symbols-outlined text-[18px] transition-transform duration-200" id="icon-print-faktur">arrow_drop_down</span>
                </button>
                <div
                    id="menu-print-faktur"
                    class="absolute right-0 mt-1 w-48 origin-top-right rounded-lg border border-outline-variant bg-surface-container-lowest shadow-lg hidden z-50 py-1"
                >
                    <a
                        href="{{ url('/sales/'.$sale->id.'/print-escp') }}"
                        class="flex items-center gap-2 px-4 py-2.5 text-sm text-on-surface hover:bg-surface-container-low transition-colors font-medium border-b border-outline-variant/30"
                    >
                        <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                        Umum (Faktur Biasa)
                    </a>
                    <a
                        href="{{ url('/sales/'.$sale->id.'/print-escp?type=khusus') }}"
                        class="flex items-center gap-2 px-4 py-2.5 text-sm text-on-surface hover:bg-surface-container-low transition-colors font-medium"
                    >
                        <span class="material-symbols-outlined text-[18px]">receipt</span>
                        Khusus
                    </a>
                </div>
            </div>

            <!-- <a
                href="{{ url('/sales/'.$sale->id.'/print-escp?preview=1') }}"
                target="_blank"
                class="inline-flex h-10 items-center gap-2 rounded-lg border border-outline-variant bg-surface-container-lowest px-md font-bold text-on-surface-variant hover:bg-surface-container-low transition-colors"
            >
                <span class="material-symbols-outlined text-[18px]">visibility</span>
                Preview Faktur (ESC/P)
            </a> -->
        </div>
    </div>

    {{-- Metadata transaksi (view + edit terintegrasi) --}}
    <section id="metadata-section" class="rounded-xl border border-outline-variant bg-surface-container-lowest shadow-sm overflow-hidden">

        {{-- VIEW MODE --}}
        <div id="meta-view" class="grid grid-cols-1 gap-lg p-lg md:grid-cols-3">
            <div class="space-y-md">
                <div>
                    <p class="text-label-md uppercase tracking-wider text-on-surface-variant">Order ID</p>
                    <p class="mt-1 text-body-lg font-bold text-on-surface">{{ $sale->no_invoice }}</p>
                </div>
                <div>
                    <p class="text-label-md uppercase tracking-wider text-on-surface-variant">Tanggal Transaksi</p>
                    <p class="mt-1 text-body-md text-on-surface">{{ optional($sale->tgl_transaksi)->format('d M Y, H:i') ?: '-' }}</p>
                </div>
            </div>
            <div class="space-y-md">
                <div>
                    <p class="text-label-md uppercase tracking-wider text-on-surface-variant">Salesman</p>
                    <p class="mt-1 text-body-md font-semibold text-on-surface">{{ $sale->sales?->nama_sales ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-label-md uppercase tracking-wider text-on-surface-variant">Customer</p>
                    <p class="mt-1 text-body-md font-semibold text-on-surface">{{ $sale->nama_pelanggan ?: 'User Umum' }}</p>
                </div>
            </div>
            <div class="space-y-md">
                <div>
                    <p class="text-label-md uppercase tracking-wider text-on-surface-variant">Tipe Pelanggan</p>
                    <span class="mt-1 inline-flex rounded-lg bg-secondary-fixed px-sm py-1 text-label-md font-bold uppercase text-on-secondary-fixed">{{ $sale->tipe_pelanggan ?: '-' }}</span>
                </div>
                <div>
                    <p class="text-label-md uppercase tracking-wider text-on-surface-variant">Metode Pembayaran</p>
                    <p class="mt-1 text-body-md text-on-surface">
                        {{ $sale->metode_bayar ?: '-' }}
                        @if ($sale->metode_bayar === 'TEMPO' && $sale->jatuh_tempo)
                            · Jatuh tempo {{ optional($sale->jatuh_tempo)->format('d M Y') }}
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- EDIT MODE: panel ini muncul saat Mode Edit aktif --}}
        <div id="meta-edit" class="hidden border-t border-orange-300/40 bg-orange-50/30 p-lg dark:bg-orange-900/10">
            <div class="mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-orange-500" style="font-size:20px">manage_accounts</span>
                <h4 class="font-bold text-on-surface">Edit Info Transaksi</h4>
                <span class="rounded-full bg-orange-100 px-2 py-0.5 text-[11px] font-bold text-orange-700">Hanya mengubah data transaksi ini</span>
            </div>

            <div class="grid grid-cols-1 gap-md md:grid-cols-2 xl:grid-cols-3">

                {{-- Tipe Pelanggan --}}
                <div>
                    <label class="mb-1 block text-label-md font-bold uppercase text-on-surface-variant" for="edit-tipe-pelanggan">Tipe Pelanggan</label>
                    <select
                        id="edit-tipe-pelanggan"
                        name="tipe_pelanggan"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-surface px-3 text-body-md text-on-surface outline-none focus:border-primary focus:ring-4 focus:ring-primary/10"
                    >
                        <option value="USER" @selected(($sale->tipe_pelanggan ?? 'USER') === 'USER')>Umum / User</option>
                        <option value="TOKO" @selected($sale->tipe_pelanggan === 'TOKO')>Toko / Mitra</option>
                        <option value="SALES" @selected($sale->tipe_pelanggan === 'SALES')>Via Sales</option>
                    </select>
                </div>

                {{-- Nama Manual (USER) --}}
                <div id="field-nama-manual">
                    <label class="mb-1 block text-label-md font-bold uppercase text-on-surface-variant" for="edit-nama-manual">Nama Pelanggan</label>
                    <input
                        type="text"
                        id="edit-nama-manual"
                        name="nama_pelanggan_manual"
                        value="{{ $sale->nama_pelanggan }}"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-surface px-3 text-body-md text-on-surface outline-none focus:border-primary focus:ring-4 focus:ring-primary/10"
                        placeholder="Nama pelanggan (opsional)"
                    >
                </div>

                {{-- Dropdown Customer (TOKO / SALES) --}}
                <div id="field-customer" class="hidden">
                    <label class="mb-1 block text-label-md font-bold uppercase text-on-surface-variant" for="edit-customer-id">Toko / Mitra</label>
                    <select
                        id="edit-customer-id"
                        name="customer_id"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-surface px-3 text-body-md text-on-surface outline-none focus:border-primary focus:ring-4 focus:ring-primary/10"
                    >
                        <option value="">— Pilih Toko —</option>
                        @foreach ($customers as $cust)
                            <option value="{{ $cust->id }}" @selected((int)$sale->customer_id === $cust->id)>{{ $cust->nama_pelanggan }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Dropdown Sales (SALES) --}}
                <div id="field-sales" class="hidden">
                    <label class="mb-1 block text-label-md font-bold uppercase text-on-surface-variant" for="edit-sales-id">Salesperson</label>
                    <select
                        id="edit-sales-id"
                        name="sales_id"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-surface px-3 text-body-md text-on-surface outline-none focus:border-primary focus:ring-4 focus:ring-primary/10"
                    >
                        <option value="">— Pilih Sales —</option>
                        @foreach ($salesPeople as $sp)
                            <option value="{{ $sp->id }}" @selected((int)$sale->sales_id === $sp->id)>{{ $sp->nama_sales }} ({{ $sp->kode_sales }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Metode Bayar --}}
                <div>
                    <label class="mb-1 block text-label-md font-bold uppercase text-on-surface-variant" for="edit-metode-bayar">Metode Pembayaran</label>
                    <select
                        id="edit-metode-bayar"
                        name="metode_bayar"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-surface px-3 text-body-md text-on-surface outline-none focus:border-primary focus:ring-4 focus:ring-primary/10"
                    >
                        <option value="CASH" @selected($sale->metode_bayar === 'CASH')>CASH</option>
                        <option value="TEMPO" @selected($sale->metode_bayar === 'TEMPO')>TEMPO</option>
                    </select>
                </div>

                {{-- Jatuh Tempo (TEMPO) --}}
                <div id="field-jatuh-tempo" class="hidden">
                    <label class="mb-1 block text-label-md font-bold uppercase text-on-surface-variant" for="edit-jatuh-tempo">Jatuh Tempo</label>
                    <input
                        type="date"
                        id="edit-jatuh-tempo"
                        name="jatuh_tempo"
                        value="{{ $sale->jatuh_tempo ? $sale->jatuh_tempo->format('Y-m-d') : '' }}"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-surface px-3 text-body-md text-on-surface outline-none focus:border-primary focus:ring-4 focus:ring-primary/10"
                    >
                </div>

            </div>
        </div>
    </section>

    {{-- Statistik pengiriman --}}
    <section class="grid grid-cols-1 gap-md sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-outline-variant bg-surface-container-lowest p-md shadow-sm">
            <p class="text-label-md uppercase text-on-surface-variant">Qty Pesanan</p>
            <p class="mt-xs text-2xl font-black text-on-surface">
                {{ format_qty($totalQty) }}
            </p>
        </div>

        <div class="rounded-xl border border-outline-variant bg-surface-container-lowest p-md shadow-sm">
            <p class="text-label-md uppercase text-on-surface-variant">Sudah Terkirim</p>
            <p class="mt-xs text-2xl font-black text-primary">
                {{ number_format($totalDelivered, 0, ',', '.') }}
            </p>
        </div>

        <div class="rounded-xl border border-outline-variant bg-surface-container-lowest p-md shadow-sm">
            <p class="text-label-md uppercase text-on-surface-variant">Sisa Kirim</p>
            <p class="mt-xs text-2xl font-black text-secondary">
                {{ number_format($totalRemaining, 0, ',', '.') }}
            </p>
        </div>

        <div class="rounded-xl border border-outline-variant bg-surface-container-lowest p-md shadow-sm">
            <p class="text-label-md uppercase text-on-surface-variant">Grand Total</p>
            <p class="mt-xs text-xl font-black text-primary">
                Rp {{ number_format((int) $sale->total_belanja, 0, ',', '.') }}
            </p>
        </div>
    </section>

    @if ($hasAnyRefund)
    {{-- Level 1: Seluruh Section bisa di Hide/Show menggunakan <details> --}}
    <details class="group rounded-lg  bg-secondary/5 overflow-hidden shadow-sm" {{ $refunds->isNotEmpty() ? '' : '' }}>
        <summary class="list-none [&::-webkit-details-marker]:hidden cursor-pointer p-3 sm:p-4 bg-secondary/10 hover:bg-secondary/20 transition-all select-none">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div class="flex items-center gap-2">
                    {{-- Icon Panah Indikator --}}
                    <span class="material-symbols-outlined text-on-surface-variant transition-transform duration-300 group-open:rotate-180">
                        keyboard_arrow_down
                    </span>
                    <h3 class="text-sm sm:text-base font-bold text-on-surface">Riwayat Refund</h3>
                    {{-- Badge jumlah refund --}}
                    <span class="rounded-full bg-error/10 px-2 py-0.5 text-[10px] font-bold text-error">
                        {{ $refunds->count() }} Transaksi
                    </span>
                </div>
                
                {{-- Info Ringkas (Tetap Muncul Saat di-Hide) --}}
                <div class="text-left sm:text-right text-xs text-on-surface-variant flex items-center gap-3">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                        <span>Total Refund: <strong class="text-error font-black">Rp {{ number_format($refundTotal, 0, ',', '.') }}</strong></span>
                        <span class="hidden sm:inline text-outline-variant/50">|</span>
                        <span>Netto: <strong class="text-on-surface">Rp {{ number_format($netTotal, 0, ',', '.') }}</strong></span>
                    </div>
                </div>
            </div>
        </summary>

        {{-- Isi Konten Saat di-Open --}}
        <div class="p-3 sm:p-4 bg-white/50 border-t border-outline-variant/20 animate-fadeIn">
            @if ($refunds->isNotEmpty())
                <div class="overflow-x-auto rounded-lg border border-outline-variant bg-surface">
                    <table class="w-full min-w-[700px] border-collapse text-left text-xs">
                        <thead class="bg-surface-variant/30 text-on-surface-variant font-semibold border-b border-outline-variant/30">
                            <tr>
                                <th class="px-3 py-2.5">No Refund</th>
                                <th class="px-3 py-2.5">Tanggal</th>
                                <th class="px-3 py-2.5">Barang Refund</th>
                                <th class="px-3 py-2.5 text-right">Total</th>
                                <th class="px-3 py-2.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10 text-on-surface">
                            @foreach ($refunds as $refund)
                                <tr class="hover:bg-secondary/5 transition-colors">
                                    <td class="px-3 py-2 font-bold whitespace-nowrap">{{ $refund->no_refund }}</td>
                                    <td class="px-3 py-2 text-on-surface-variant whitespace-nowrap">{{ optional($refund->refund_at)->format('d M Y H:i') ?: '-' }}</td>
                                    
                                    {{-- Level 2: Detail Barang Hide/Show --}}
                                    <td class="px-3 py-2">
                                        @if ($refund->details->count() <= 1)
                                            @php $detail = $refund->details->first(); @endphp
                                            <span class="text-xs text-on-surface">
                                                {{ $detail->product?->nama_barang ?? $detail->kode_barang }}
                                                <strong class="text-on-surface-variant">x{{ format_qty($detail->qty_refund) }}</strong>
                                            </span>
                                        @else
                                            <details class="group/item">
                                                <summary class="list-none [&::-webkit-details-marker]:hidden flex items-center gap-1 cursor-pointer text-primary font-bold hover:underline select-none">
                                                    <span>{{ $refund->details->count() }} Barang</span>
                                                    <span class="material-symbols-outlined text-[14px] group-open/item:rotate-180 transition-transform">expand_more</span>
                                                </summary>
                                                <div class="mt-1 flex flex-col gap-1 border-l-2 border-primary/30 pl-2 py-1 bg-surface-variant/10 rounded-r shadow-inner">
                                                    @foreach ($refund->details as $refundDetail)
                                                        <div class="text-[11px] leading-tight text-on-surface-variant">
                                                            • {{ $refundDetail->product?->nama_barang ?? $refundDetail->kode_barang }}
                                                            <strong class="text-on-surface">x{{ format_qty($refundDetail->qty_refund) }}</strong>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </details>
                                        @endif
                                    </td>

                                    <td class="px-3 py-2 text-right font-bold text-error whitespace-nowrap">Rp {{ number_format((int) $refund->refund_total, 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-center whitespace-nowrap">
                                        <a href="{{ route('sales.refund.print', ['refund' => $refund->id, 'auto_print' => 1]) }}" target="_blank" rel="noopener" class="inline-flex h-7 items-center gap-1 rounded border border-outline-variant px-2 text-[11px] font-bold text-primary hover:bg-surface-container-high transition-all">
                                            <span class="material-symbols-outlined text-[15px]">print</span>
                                            Cetak
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif (! blank($sale->refund_reason))
                <div class="rounded-lg bg-surface p-3 text-xs text-on-surface-variant border border-outline-variant/30 italic">
                    Catatan: {{ $sale->refund_reason }}
                </div>
            @endif
        </div>
    </details>
@endif

<style>
    /* Efek animasi halus saat menu dibuka */
    .animate-fadeIn {
        animation: fadeIn 0.3s ease-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>


    @if (! $isFullyRefunded && $refundDetailColumnReady && $refundOrderColumnReady)
    <div class=" pb-0">
        <button
            type="button"
            id="btn-toggle-refund"
            class="inline-flex h-10 items-center gap-2 px-md font-bold text-primary transition-colors hover:bg-surface-container-high"
        >
            <span class="material-symbols-outlined text-[18px]">edit_note</span>
            <span>Proses Refund Barang</span>
        </button>
    </div>

    <div id="container-form-refund" class="hidden p-lg">
        <form
            method="POST"
            action="{{ route('sales.refund', $sale->id) }}"
            id="partial-refund-form"
            class="rounded-xl border border-outline-variant bg-surface p-md"
            onsubmit="return window.confirm('Proses refund sesuai qty yang diisi? Stok akan dikembalikan sesuai qty refund.');"
        >
            @csrf

            <div class="flex flex-col gap-sm md:flex-row md:items-center md:justify-between mb-3">
                <div>
                    <h4 class="font-headline-sm text-headline-sm font-bold text-on-surface">Refund Barang</h4>
                    <p class="mt-1 text-label-md text-on-surface-variant">
                        Isi qty pada barang yang dikembalikan. Kosongkan atau isi 0 untuk barang yang tidak refund.
                    </p>
                </div>

                <button
                    type="button"
                    id="fill-full-refund"
                    class="h-10 rounded-lg border border-outline-variant px-md font-bold text-on-surface-variant transition-colors hover:bg-surface-container-high"
                >
                    Isi Full Refund
                </button>
            </div>

            <div class="mt-md overflow-x-auto">
                <table class="w-full min-w-[760px] border-collapse text-left text-body-sm">
                    <thead class="bg-surface-variant/30 text-label-md uppercase text-on-surface-variant">
                        <tr>
                            <th class="border-b border-outline-variant px-md py-3">Produk</th>
                            <th class="w-24 border-b border-outline-variant px-md py-3 text-right">Qty Jual</th>
                            <th class="w-28 border-b border-outline-variant px-md py-3 text-right">Sudah Refund</th>
                            <th class="w-28 border-b border-outline-variant px-md py-3 text-right">Sisa Bisa Refund</th>
                            <th class="w-32 border-b border-outline-variant px-md py-3 text-right">Qty Refund</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/30">
                        @foreach ($details as $detail)
                            @php
                                $qty = (float) $detail->qty;
                                $alreadyRefunded = (float) ($detail->qty_refund ?? 0);
                                $canRefund = max(0, $qty - $alreadyRefunded);
                            @endphp
                            <tr>
                                <td class="px-md py-3">
                                    <p class="font-semibold text-on-surface">{{ $detail->product?->nama_barang ?? 'Barang tidak ditemukan' }}</p>
                                    <p class="text-label-sm uppercase text-on-surface-variant">{{ $detail->kode_barang }}</p>
                                </td>
                                <td class="px-md py-3 text-right">{{ format_qty($qty) }}</td>
                                <td class="px-md py-3 text-right text-secondary">{{ format_qty($alreadyRefunded) }}</td>
                                <td class="px-md py-3 text-right font-semibold text-primary">{{ format_qty($canRefund) }}</td>
                                <td class="px-md py-3 text-right">
                                    <input
                                        type="number"
                                        step="any"
                                        name="refund_qty[{{ $detail->id }}]"
                                        value="0"
                                        min="0"
                                        max="{{ $canRefund }}"
                                        data-max-refund="{{ $canRefund }}"
                                        @disabled($canRefund <= 0)
                                        class="refund-qty-input h-9 w-24 rounded-lg border border-outline-variant bg-surface px-2 text-right outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 disabled:bg-surface-container-high disabled:text-on-surface-variant"
                                    >
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-md mb-3">
                <label class="text-label-md font-bold uppercase text-on-surface-variant" for="refund_reason">
                    Alasan Refund
                </label>
                <textarea
                    id="refund_reason"
                    name="refund_reason"
                    rows="2"
                    class="mt-1 block w-full rounded-xl border border-outline-variant bg-surface px-md py-sm text-body-md outline-none focus:border-primary focus:ring-4 focus:ring-primary/10"
                    placeholder="Contoh: barang dikembalikan pelanggan karena salah ukuran atau batal beli"
                ></textarea>
            </div>

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="inline-flex h-10 items-center gap-2 rounded-lg bg-error px-lg font-bold text-white transition-all hover:brightness-95"
                >
                    <span class="material-symbols-outlined text-[18px]">assignment_return</span>
                    Proses Refund
                </button>
            </div>
        </form>
    </div>

    
@endif

{{-- Detail produk --}}
    <section class="overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest shadow-sm">
        <form method="POST" action="{{ route('sales.update', $sale->id) }}" id="edit-sale-form">
            @csrf
            @method('PUT')

            {{-- Hidden inputs untuk header transaksi — di-sync oleh JS dari panel meta-edit --}}
            <input type="hidden" id="hidden-tipe-pelanggan"          name="tipe_pelanggan"          value="{{ $sale->tipe_pelanggan }}">
            <input type="hidden" id="hidden-nama-pelanggan-manual"   name="nama_pelanggan_manual"   value="{{ $sale->nama_pelanggan }}">
            <input type="hidden" id="hidden-alamat-pelanggan-manual" name="alamat_pelanggan_manual" value="{{ $sale->alamat_pelanggan }}">
            <input type="hidden" id="hidden-customer-id"             name="customer_id"             value="{{ $sale->customer_id }}">
            <input type="hidden" id="hidden-sales-id"                name="sales_id"                value="{{ $sale->sales_id }}">
            <input type="hidden" id="hidden-metode-bayar"            name="metode_bayar"            value="{{ $sale->metode_bayar }}">
            <input type="hidden" id="hidden-jatuh-tempo"             name="jatuh_tempo"             value="{{ $sale->jatuh_tempo ? $sale->jatuh_tempo->format('Y-m-d') : '' }}">

            <div class="flex flex-col gap-md border-b border-outline-variant p-lg md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="font-headline-md text-headline-md text-on-surface">
                        Daftar Produk Terjual
                    </h3>

                    <p class="mt-1 text-label-md text-on-surface-variant">
                        Gunakan Mode Edit untuk mengubah qty pesanan, qty terkirim, atau menambah produk.
                    </p>
                </div>

                <button
                    type="button"
                    id="open-product-search"
                    class="sale-edit-control hidden h-10 items-center gap-2 rounded-lg bg-blue-500 px-md font-bold text-white transition-all hover:brightness-95"
                >
                    <span class="material-symbols-outlined text-[18px]">add_circle</span>
                    Tambah Produk
                </button>
            </div>

            <div id="product-search-panel" class="hidden border-b border-outline-variant bg-surface-container-low p-md">
                <div class="relative">
                    <span
                        class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant"
                        style="font-size: 20px; line-height: 20px;"
                    >
                        search
                    </span>

                    <input
                        type="text"
                        id="detail-product-search"
                        class="block h-11 w-full rounded-xl border border-outline-variant bg-surface pl-12 pr-4 text-body-md text-on-surface outline-none focus:border-primary focus:ring-4 focus:ring-primary/10"
                        placeholder="Cari nama atau kode barang..."
                        autocomplete="off"
                    >
                </div>

                <div
                    id="detail-product-results"
                    class="mt-sm max-h-64 overflow-y-auto rounded-xl border border-outline-variant bg-surface-container-lowest"
                ></div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1080px] border-collapse text-left">
                    <thead class="bg-surface-variant/30 text-label-md uppercase tracking-wider text-on-surface-variant">
                        <tr>
                            <th class="w-12 border-b border-outline-variant px-md py-3 text-center">No</th>
                            <th class="border-b border-outline-variant px-md py-3">Produk</th>
                            <th class="w-24 border-b border-outline-variant px-md py-3 text-right">Qty</th>
                            <th class="w-28 border-b border-outline-variant px-md py-3 text-right">Terkirim</th>
                            <th class="w-28 border-b border-outline-variant px-md py-3 text-right">Sisa</th>
                            <th class="w-24 border-b border-outline-variant px-md py-3 text-center">Satuan</th>
                            <th class="w-36 border-b border-outline-variant px-md py-3 text-right">Harga Satuan</th>
                            <th class="w-40 border-b border-outline-variant px-md py-3 text-right">Subtotal</th>
                            <th class="sale-edit-column hidden w-16 border-b border-outline-variant px-md py-3 text-center"></th>
                        </tr>
                    </thead>

                    <tbody id="sale-detail-rows" class="divide-y divide-outline-variant/30">
                        @forelse ($details as $index => $detail)
                            @php
                                $qty = (float) $detail->qty;
                                $delivered = min($qty, max(0, (float) $detail->qty_terkirim));
                                $remaining = max(0, $qty - $delivered);
                            @endphp

                            <tr
                                class="sale-item-row transition-colors hover:bg-surface-container-low"
                                data-code="{{ $detail->kode_barang }}"
                                data-price="{{ (int) $detail->harga_jual }}"
                                data-original-delivered="{{ $delivered }}"
                            >
                                <td class="sale-row-number px-md py-4 text-center text-on-surface-variant">
                                    {{ $index + 1 }}
                                </td>

                                <td class="px-md py-4">
                                    <input type="hidden" name="kode_barang[]" value="{{ $detail->kode_barang }}">

                                    <div class="flex flex-col">
                                        <span class="font-semibold text-on-surface">
                                            {{ $detail->product?->nama_barang ?? 'Barang tidak ditemukan' }}
                                        </span>

                                        <span class="text-label-sm uppercase text-on-surface-variant">
                                            {{ $detail->kode_barang }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-md py-4 text-right">
                                    <span class="sale-view-value font-medium text-on-surface">
                                        {{ format_qty($qty) }}
                                    </span>

                                    <input
                                        type="number"
                                        step="any"
                                        name="qty[]"
                                        value="{{ $qty }}"
                                        min="0.001"
                                        class="sale-edit-control sale-qty-input hidden h-9 w-20 rounded-lg border border-outline-variant bg-surface px-2 text-right outline-none focus:border-primary focus:ring-2 focus:ring-primary/10"
                                    >
                                </td>

                                <td class="px-md py-4 text-right">
                                    <span class="sale-view-value font-medium text-primary">
                                        {{ format_qty($delivered) }}
                                    </span>

                                    <input
                                        type="number"
                                        step="any"
                                        name="qty_terkirim[]"
                                        value="{{ $delivered }}"
                                        min="0"
                                        max="{{ $qty }}"
                                        class="sale-edit-control sale-delivered-input hidden h-9 w-20 rounded-lg border border-outline-variant bg-surface px-2 text-right outline-none focus:border-primary focus:ring-2 focus:ring-primary/10"
                                    >
                                </td>

                                <td class="sale-remaining px-md py-4 text-right font-semibold text-secondary">
                                    {{ format_qty($remaining) }}
                                </td>

                                <td class="px-md py-4 text-center">
                                    <span class="rounded-lg bg-surface-container-high px-2 py-1 text-label-sm font-bold uppercase text-on-surface-variant">
                                        {{ $detail->product?->satuan ?? 'Unit' }}
                                    </span>
                                </td>

                                <td class="px-md py-4 text-right text-on-surface">
                                    <span class="sale-view-value font-medium text-on-surface">
                                        Rp {{ number_format((int) $detail->harga_jual, 0, ',', '.') }}
                                    </span>

                                    <input
                                        type="number"
                                        name="harga_jual[]"
                                        value="{{ (int) $detail->harga_jual }}"
                                        min="0"
                                        class="sale-edit-control sale-price-input hidden h-9 w-28 rounded-lg border border-outline-variant bg-surface px-2 text-right outline-none focus:border-primary focus:ring-2 focus:ring-primary/10"
                                    >
                                </td>

                                <td class="sale-subtotal px-md py-4 text-right font-semibold text-on-surface">
                                    Rp {{ number_format((int) $detail->subtotal, 0, ',', '.') }}
                                </td>

                                <td class="sale-edit-column hidden px-md py-4 text-center">
                                    <button
                                        type="button"
                                        class="remove-sale-row inline-flex h-8 w-8 items-center justify-center rounded-lg text-error transition-colors hover:bg-error/10"
                                        title="Hapus barang"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr id="empty-sale-row">
                                <td colspan="9" class="px-md py-xl text-center text-on-surface-variant">
                                    Detail barang belum tersedia.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Total --}}
            <div class="border-t border-outline-variant bg-surface-variant/10 p-lg">
                <div class="flex flex-col gap-lg md:flex-row md:justify-between">
                    <div class="max-w-md">
                        <!-- <p class="mb-2 text-label-md uppercase text-on-surface-variant">
                            Keterangan
                        </p>

                        <p class="text-body-md italic text-on-surface">
                            Qty sisa kirim dihitung otomatis dari Qty Pesanan dikurangi Qty Terkirim.
                        </p> -->
                    </div>

                    <div class="flex w-full max-w-[360px] flex-col gap-sm">
                        <div class="flex justify-between">
                            <span class="text-body-md text-on-surface-variant">Qty Pesanan</span>
                            <span id="summary-order-qty" class="text-body-md font-semibold text-on-surface">
                                {{ format_qty($totalQty) }}
                            </span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-body-md text-on-surface-variant">Sudah Terkirim</span>
                            <span id="summary-delivered-qty" class="text-body-md font-semibold text-primary">
                                {{ number_format($totalDelivered, 0, ',', '.') }}
                            </span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-body-md text-on-surface-variant">Sisa / Akan Dikirim</span>
                            <span id="summary-remaining-qty" class="text-body-md font-semibold text-secondary">
                                {{ number_format($totalRemaining, 0, ',', '.') }}
                            </span>
                        </div>

                        <div class="mt-sm flex justify-between border-t-2 border-primary/20 pt-md">
                            <span class="font-headline-md text-headline-md text-on-surface">
                                Grand Total
                            </span>

                            <span id="detail-grand-total" class="font-headline-md text-headline-md font-bold text-primary">
                                Rp {{ number_format((int) $sale->total_belanja, 0, ',', '.') }}
                            </span>
                        </div>

                        @if ($hasAnyRefund)
                            <div class="flex justify-between">
                                <span class="text-body-md text-on-surface-variant">Total Refund</span>
                                <span class="text-body-md font-semibold text-error">
                                    Rp {{ number_format($refundTotal, 0, ',', '.') }}
                                </span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-body-md text-on-surface-variant">Netto Setelah Refund</span>
                                <span class="text-body-md font-bold text-primary">
                                    Rp {{ number_format($netTotal, 0, ',', '.') }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Footer edit --}}
            <div class="sale-edit-control hidden items-center justify-end gap-sm border-t border-outline-variant bg-surface-container-lowest px-lg py-md">
                <button
                    type="button"
                    id="cancel-sale-edit"
                    class="h-10 rounded-lg border border-outline-variant px-lg font-bold text-on-surface-variant transition-colors hover:bg-surface-container-high"
                >
                    Batal
                </button>

                <button
                    type="submit"
                    class="inline-flex h-10 items-center gap-2 rounded-lg bg-primary px-lg font-bold text-on-primary transition-all hover:brightness-110"
                >
                    <span class="material-symbols-outlined text-[18px]">save</span>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </section>
</div>
@endsection

@push('scripts')
<script>

document.addEventListener('DOMContentLoaded', function () {
    const btnToggle = document.getElementById('btn-toggle-refund');
    const containerForm = document.getElementById('container-form-refund');

    const searchProductsUrl = @json(route('sales.search_products'));
    const getPriceUrl = @json(route('sales.get_price'));
    const customerType = @json($sale->tipe_pelanggan);
    const targetId = @json($targetId);

    const toggleButton = document.getElementById('toggle-edit-sale');
    const cancelButton = document.getElementById('cancel-sale-edit');
    const addButton = document.getElementById('open-product-search');
    const searchPanel = document.getElementById('product-search-panel');
    const searchInput = document.getElementById('detail-product-search');
    const results = document.getElementById('detail-product-results');
    const rowsContainer = document.getElementById('sale-detail-rows');

    let editing = false;
    let searchTimer = null;

    function rupiah(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
    }
    if (btnToggle && containerForm) {
                btnToggle.addEventListener('click', function () {
                    // Toggle class 'hidden' milik Tailwind
                    containerForm.classList.toggle('hidden');
                    
                    // Opsional: Mengubah teks tombol secara dinamis
                    const isHidden = containerForm.classList.contains('hidden');
                    btnToggle.querySelector('span:not(.material-symbols-outlined)').textContent = isHidden ? 'Proses Refund Barang' : 'Sembunyikan Form';
                });
            }
    function numberId(value) {
        return Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 3 });
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (character) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[character];
        });
    }

    function editControls() {
        return document.querySelectorAll('.sale-edit-control');
    }

    function editColumns() {
        return document.querySelectorAll('.sale-edit-column');
    }

    // ── Data untuk header edit ───────────────────────────────────────────
    const metaView = document.getElementById('meta-view');
    const metaEdit = document.getElementById('meta-edit');
    const tipeSel  = document.getElementById('edit-tipe-pelanggan');
    const metodeSel= document.getElementById('edit-metode-bayar');
    const fieldNama    = document.getElementById('field-nama-manual');
    const fieldCustomer= document.getElementById('field-customer');
    const fieldSales   = document.getElementById('field-sales');
    const fieldTempo   = document.getElementById('field-jatuh-tempo');

    // ── Sync hidden form inputs dari panel meta-edit ──────────────────────
    function syncHiddenHeaderFields() {
        const tipe   = tipeSel   ? tipeSel.value   : null;
        const metode = metodeSel ? metodeSel.value : null;

        const hTipe    = document.getElementById('hidden-tipe-pelanggan');
        const hNama    = document.getElementById('hidden-nama-pelanggan-manual');
        const hAlamat  = document.getElementById('hidden-alamat-pelanggan-manual');
        const hCust    = document.getElementById('hidden-customer-id');
        const hSales   = document.getElementById('hidden-sales-id');
        const hMetode  = document.getElementById('hidden-metode-bayar');
        const hTempo   = document.getElementById('hidden-jatuh-tempo');

        const custSel  = document.getElementById('edit-customer-id');
        const salesSel = document.getElementById('edit-sales-id');
        const namaInp  = document.getElementById('edit-nama-manual');
        const tempoInp = document.getElementById('edit-jatuh-tempo');

        if (tipe   && hTipe)   hTipe.value   = tipe;
        if (metode && hMetode) hMetode.value = metode;

        if (tipe === 'USER') {
            if (hNama)   hNama.value   = namaInp  ? namaInp.value  : '';
            if (hAlamat) hAlamat.value = '';
            if (hCust)   hCust.value   = '';
            if (hSales)  hSales.value  = '';
        } else if (tipe === 'TOKO') {
            if (hCust)   hCust.value   = custSel  ? custSel.value  : '';
            if (hNama)   hNama.value   = '';
            if (hAlamat) hAlamat.value = '';
            if (hSales)  hSales.value  = '';
        } else if (tipe === 'SALES') {
            if (hCust)   hCust.value   = custSel  ? custSel.value  : '';
            if (hSales)  hSales.value  = salesSel ? salesSel.value : '';
            if (hNama)   hNama.value   = '';
            if (hAlamat) hAlamat.value = '';
        }

        if (metode === 'TEMPO') {
            if (hTempo) hTempo.value = tempoInp ? tempoInp.value : '';
        } else {
            if (hTempo) hTempo.value = '';
        }
    }

    function syncHeaderFields() {
        const tipe   = tipeSel ? tipeSel.value : 'USER';
        const metode = metodeSel ? metodeSel.value : 'CASH';

        // Tipe pelanggan — tampilkan field yang relevan
        if (fieldNama)     fieldNama.classList.toggle('hidden',  tipe !== 'USER');
        if (fieldCustomer) fieldCustomer.classList.toggle('hidden', tipe === 'USER');
        if (fieldSales)    fieldSales.classList.toggle('hidden',  tipe !== 'SALES');

        // Metode bayar
        if (fieldTempo) fieldTempo.classList.toggle('hidden', metode !== 'TEMPO');

        // Selalu sync hidden inputs saat UI berubah
        syncHiddenHeaderFields();
    }

    if (tipeSel)   tipeSel.addEventListener('change', syncHeaderFields);
    if (metodeSel) metodeSel.addEventListener('change', syncHeaderFields);

    // Sync saat nilai field lain berubah (nama manual, customer, sales, jatuh tempo)
    document.getElementById('edit-nama-manual')?.addEventListener('input', syncHiddenHeaderFields);
    document.getElementById('edit-customer-id')?.addEventListener('change', syncHiddenHeaderFields);
    document.getElementById('edit-sales-id')?.addEventListener('change', syncHiddenHeaderFields);
    document.getElementById('edit-jatuh-tempo')?.addEventListener('change', syncHiddenHeaderFields);


    function setEditing(active) {
        editing = active;

        // Toggle panel meta view/edit
        if (metaView) metaView.classList.toggle('hidden', active);
        if (metaEdit) metaEdit.classList.toggle('hidden', !active);

        // Sinkronkan field header saat mode edit dibuka
        if (active) syncHeaderFields();

        editControls().forEach(function (element) {
            element.classList.toggle('hidden', !active);

            if (element.tagName === 'DIV') {
                element.classList.toggle('flex', active);
            }
        });

        editColumns().forEach(function (element) {
            element.classList.toggle('hidden', !active);
        });

        document.querySelectorAll('.sale-view-value').forEach(function (element) {
            element.classList.toggle('hidden', active);
        });

        if (toggleButton) {
            toggleButton.innerHTML = active
                ? '<span class="material-symbols-outlined text-[18px]">close</span>Tutup Edit'
                : '<span class="material-symbols-outlined text-[18px]">edit_note</span>Mode Edit';

            toggleButton.classList.toggle('bg-orange-600', active);
        }

        if (!active && searchPanel) {
            searchPanel.classList.add('hidden');
        }
    }

    function recalculate() {
        let grandTotal = 0;
        let totalQty = 0;
        let totalDelivered = 0;
        let totalRemaining = 0;

        document.querySelectorAll('.sale-item-row').forEach(function (row) {
            const qtyInput = row.querySelector('.sale-qty-input');
            const deliveredInput = row.querySelector('.sale-delivered-input');
            const priceInput = row.querySelector('.sale-price-input');

            const qty = Math.max(0, Number(qtyInput?.value || 0));
            let delivered = Math.max(0, Number(deliveredInput?.value || 0));

            if (delivered > qty) {
                delivered = qty;

                if (deliveredInput) {
                    deliveredInput.value = String(qty);
                }
            }

            if (deliveredInput) {
                deliveredInput.max = String(qty);
            }

            let price = priceInput ? Number(priceInput.value || 0) : Number(row.dataset.price || 0);
            row.dataset.price = String(price);

            const remaining = Math.max(0, qty - delivered);
            const subtotal = qty * price;

            row.querySelector('.sale-remaining').textContent = numberId(remaining);
            row.querySelector('.sale-subtotal').textContent = rupiah(subtotal);

            totalQty += qty;
            totalDelivered += delivered;
            totalRemaining += remaining;
            grandTotal += subtotal;
        });

        document.getElementById('summary-order-qty').textContent = numberId(totalQty);
        document.getElementById('summary-delivered-qty').textContent = numberId(totalDelivered);
        document.getElementById('summary-remaining-qty').textContent = numberId(totalRemaining);
        document.getElementById('detail-grand-total').textContent = rupiah(grandTotal);
    }

    function renumber() {
        document.querySelectorAll('.sale-item-row').forEach(function (row, index) {
            row.querySelector('.sale-row-number').textContent = String(index + 1);
        });
    }

    function bindRow(row) {
        row.querySelector('.sale-qty-input')?.addEventListener('input', recalculate);
        row.querySelector('.sale-delivered-input')?.addEventListener('input', recalculate);
        row.querySelector('.sale-price-input')?.addEventListener('input', recalculate);

        row.querySelector('.remove-sale-row')?.addEventListener('click', function () {
            row.remove();
            renumber();
            recalculate();
        });
    }

    async function loadProducts(keyword) {
        if (!results) {
            return;
        }

        const url = new URL(searchProductsUrl, window.location.origin);
        url.searchParams.set('q', keyword.trim());

        results.innerHTML = '<div class="p-md text-on-surface-variant">Memuat barang...</div>';

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const payload = await response.json();
            const products = payload.data || [];

            if (!products.length) {
                results.innerHTML = '<div class="p-md text-on-surface-variant">Barang tidak ditemukan.</div>';
                return;
            }

            results.innerHTML = products.map(function (product) {
                return `
                    <button
                        type="button"
                        class="product-result flex w-full items-center justify-between gap-md border-b border-outline-variant px-md py-3 text-left transition-colors last:border-b-0 hover:bg-surface-container-low"
                        data-code="${escapeHtml(product.kode_barang)}"
                        data-name="${escapeHtml(product.nama_barang)}"
                        data-unit="${escapeHtml(product.satuan || 'Unit')}"
                    >
                        <div>
                            <p class="font-semibold text-on-surface">${escapeHtml(product.nama_barang)}</p>
                            <p class="text-label-sm uppercase text-on-surface-variant">
                                ${escapeHtml(product.kode_barang)}
                            </p>
                        </div>

                        <div class="text-right">
                            <p class="text-label-sm text-on-surface-variant">
                                Stok ${numberId(product.sisa_stok)} ${escapeHtml(product.satuan || '')}
                            </p>

                            <span class="mt-1 inline-flex rounded-lg border border-outline-variant px-sm py-1 text-label-sm font-bold text-primary">
                                Tambah
                            </span>
                        </div>
                    </button>
                `;
            }).join('');

            results.querySelectorAll('.product-result').forEach(function (button) {
                button.addEventListener('click', function () {
                    addProduct({
                        kode_barang: button.dataset.code,
                        nama_barang: button.dataset.name,
                        satuan: button.dataset.unit
                    });
                });
            });
        } catch (error) {
            results.innerHTML = '<div class="p-md font-bold text-error">Gagal memuat barang.</div>';
        }
    }

    async function getProductPrice(code) {
        try {
            const url = new URL(getPriceUrl, window.location.origin);

            url.searchParams.set('kode_barang', code);
            url.searchParams.set('tipe_pelanggan', customerType || 'USER');

            if (targetId) {
                url.searchParams.set('target_id', targetId);
            }

            const response = await fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const payload = await response.json();

            if (response.ok && payload.success) {
                return Number(payload.harga_jual || 0);
            }
        } catch (error) {}

        return 0;
    }

    async function addProduct(product) {
        const duplicate = Array.from(document.querySelectorAll('.sale-item-row'))
            .some(function (row) {
                return row.dataset.code === product.kode_barang;
            });

        if (duplicate) {
            alert('Barang tersebut sudah ada dalam transaksi.');
            return;
        }

        try {
            const price = await getProductPrice(product.kode_barang);

            document.getElementById('empty-sale-row')?.remove();

            const row = document.createElement('tr');

            row.className = 'sale-item-row transition-colors hover:bg-surface-container-low';
            row.dataset.code = product.kode_barang;
            row.dataset.price = String(price);
            row.dataset.originalDelivered = '0';

            row.innerHTML = `
                <td class="sale-row-number px-md py-4 text-center text-on-surface-variant"></td>

                <td class="px-md py-4">
                    <input type="hidden" name="kode_barang[]" value="${escapeHtml(product.kode_barang)}">

                    <div class="flex flex-col">
                        <span class="font-semibold text-on-surface">${escapeHtml(product.nama_barang)}</span>
                        <span class="text-label-sm uppercase text-on-surface-variant">${escapeHtml(product.kode_barang)}</span>
                    </div>
                </td>

                <td class="px-md py-4 text-right">
                    <input
                        type="number"
                        step="any"
                        name="qty[]"
                        value="1"
                        min="0.001"
                        class="sale-edit-control sale-qty-input h-9 w-20 rounded-lg border border-outline-variant bg-surface px-2 text-right outline-none focus:border-primary focus:ring-2 focus:ring-primary/10"
                    >
                </td>

                <td class="px-md py-4 text-right">
                    <input
                        type="number"
                        step="any"
                        name="qty_terkirim[]"
                        value="0"
                        min="0"
                        max="1"
                        class="sale-edit-control sale-delivered-input h-9 w-20 rounded-lg border border-outline-variant bg-surface px-2 text-right outline-none focus:border-primary focus:ring-2 focus:ring-primary/10"
                    >
                </td>

                <td class="sale-remaining px-md py-4 text-right font-semibold text-secondary">1</td>

                <td class="px-md py-4 text-center">
                    <span class="rounded-lg bg-surface-container-high px-2 py-1 text-label-sm font-bold uppercase text-on-surface-variant">
                        ${escapeHtml(product.satuan || 'Unit')}
                    </span>
                </td>

                <td class="px-md py-4 text-right">
                    <span class="sale-view-value hidden font-medium text-on-surface">${rupiah(price)}</span>
                    <input
                        type="number"
                        name="harga_jual[]"
                        value="${price}"
                        min="0"
                        placeholder="Harga..."
                        class="sale-edit-control sale-price-input h-9 w-28 rounded-lg border border-outline-variant bg-surface px-2 text-right outline-none focus:border-primary focus:ring-2 focus:ring-primary/10"
                    >
                </td>

                <td class="sale-subtotal px-md py-4 text-right font-semibold">
                    ${rupiah(price)}
                </td>

                <td class="sale-edit-column px-md py-4 text-center">
                    <button
                        type="button"
                        class="remove-sale-row inline-flex h-8 w-8 items-center justify-center rounded-lg text-error transition-colors hover:bg-error/10"
                        title="Hapus barang"
                    >
                        <span class="material-symbols-outlined text-[18px]">delete</span>
                    </button>
                </td>
            `;

            rowsContainer.appendChild(row);
            bindRow(row);
            renumber();
            recalculate();

            if (searchInput) {
                searchInput.value = '';
            }

            if (searchPanel) {
                searchPanel.classList.add('hidden');
            }
        } catch (error) {
            alert(error.message || 'Gagal menambahkan barang.');
        }
    }

    document.querySelectorAll('.sale-item-row').forEach(bindRow);

    toggleButton?.addEventListener('click', function () {
        if (editing) {
            window.location.reload();
            return;
        }

        setEditing(true);
    });

    cancelButton?.addEventListener('click', function () {
        window.location.reload();
    });

    addButton?.addEventListener('click', function () {
        if (!searchPanel || !searchInput) {
            return;
        }

        searchPanel.classList.toggle('hidden');

        if (!searchPanel.classList.contains('hidden')) {
            searchInput.focus();
            loadProducts('');
        }
    });

    searchInput?.addEventListener('input', function () {
        clearTimeout(searchTimer);

        searchTimer = setTimeout(function () {
            loadProducts(searchInput.value);
        }, 250);
    });

    document.getElementById('edit-sale-form')?.addEventListener('submit', function (event) {
        // Pastikan hidden header fields ter-sync sebelum submit
        syncHiddenHeaderFields();

        if (!document.querySelector('.sale-item-row')) {
            event.preventDefault();
            alert('Transaksi wajib memiliki minimal satu barang.');
            return;
        }

        const invalid = Array.from(document.querySelectorAll('.sale-item-row'))
            .some(function (row) {
                const qty = Number(row.querySelector('.sale-qty-input')?.value || 0);
                const delivered = Number(row.querySelector('.sale-delivered-input')?.value || 0);
                const price = Number(row.querySelector('.sale-price-input')?.value || 0);

                return qty <= 0 || delivered < 0 || delivered > qty || price <= 0;
            });

        if (invalid) {
            event.preventDefault();
            alert('Periksa kembali Qty Pesanan, Qty Terkirim, dan pastikan Harga Jual > 0.');
        }
    });


    const fullRefundButton = document.getElementById('fill-full-refund');
    fullRefundButton?.addEventListener('click', function () {
        document.querySelectorAll('.refund-qty-input').forEach(function (input) {
            input.value = input.dataset.maxRefund || '0';
        });
    });

    document.getElementById('partial-refund-form')?.addEventListener('submit', function (event) {
        const totalQty = Array.from(document.querySelectorAll('.refund-qty-input'))
            .reduce(function (sum, input) {
                return sum + Number(input.value || 0);
            }, 0);

        if (totalQty <= 0) {
            event.preventDefault();
            alert('Isi minimal satu qty barang yang akan di-refund.');
        }
    });

    // Toggle dropdown Print Faktur
    const btnPrintFaktur = document.getElementById('btn-print-faktur');
    const menuPrintFaktur = document.getElementById('menu-print-faktur');
    const iconPrintFaktur = document.getElementById('icon-print-faktur');

    if (btnPrintFaktur && menuPrintFaktur) {
        btnPrintFaktur.addEventListener('click', function (e) {
            e.stopPropagation();
            const isHidden = menuPrintFaktur.classList.toggle('hidden');
            if (iconPrintFaktur) {
                if (isHidden) {
                    iconPrintFaktur.style.transform = 'rotate(0deg)';
                } else {
                    iconPrintFaktur.style.transform = 'rotate(180deg)';
                }
            }
        });

        // Hide dropdown immediately when any link inside it is clicked
        menuPrintFaktur.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                menuPrintFaktur.classList.add('hidden');
                if (iconPrintFaktur) {
                    iconPrintFaktur.style.transform = 'rotate(0deg)';
                }
            });
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('#print-faktur-dropdown')) {
                menuPrintFaktur.classList.add('hidden');
                if (iconPrintFaktur) {
                    iconPrintFaktur.style.transform = 'rotate(0deg)';
                }
            }
        });
    }

    setEditing(false);
});
</script>
@endpush
