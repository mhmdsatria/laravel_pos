@extends('layouts.app')

@section('title', 'Nota Pembelian / Restock Barang')

@section('content')
@php
    $purchaseSummary = $purchaseSummary ?? [
        'total_nota' => 0,
        'total_qty' => 0,
        'total_baris_item' => 0,
        'total_harga' => 0,
        'total_dibayar' => 0,
        'total_sisa' => 0,
    ];

    $paymentBadge = function (string $status): string {
        return match ($status) {
            'Lunas' => 'bg-emerald-100 text-emerald-800',
            'Dibayar Sebagian' => 'bg-amber-100 text-amber-800',
            default => 'bg-red-100 text-red-700',
        };
    };
@endphp

<style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; vertical-align: middle; }
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .text-primary-emerald { color: #006c49; }
    .bg-primary-emerald { background-color: #006c49; }
    .modal-overlay { background-color: rgba(17, 28, 45, 0.4); }
    @keyframes modalFadeIn { from { opacity: 0; transform: scale(0.98) translateY(-10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
    .animate-modal { animation: modalFadeIn 0.12s ease-out; }
    .dropdown-list { position: absolute; z-index: 60; width: 100%; max-height: 220px; overflow-y: auto; background: white; border: 1px solid #c4c5d5; border-radius: 0.5rem; margin-top: 4px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }

    .purchase-action-cell { min-width: 300px; }
    .purchase-action-wrap { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 6px; }
    .purchase-action-btn { display: inline-flex; align-items: center; justify-content: center; gap: 4px; height: 32px; padding: 0 9px; border-radius: 10px; border: 1px solid #d9dbe7; background: #fff; font-size: 11px; font-weight: 800; line-height: 1; white-space: nowrap; transition: all .12s ease; box-shadow: 0 1px 2px rgba(15, 23, 42, .05); }
    .purchase-action-btn:hover { background: #f8fafc; transform: translateY(-1px); }
    .purchase-action-btn .material-symbols-outlined { font-size: 17px; line-height: 1; }
    .purchase-action-btn.is-pay { border-color: #f59e0b; background: #fffbeb; color: #92400e; }
    .purchase-action-btn.is-pay:hover { background: #fef3c7; }
    .purchase-action-btn.is-print { border-color: #10b981; color: #006c49; }
    .payment-modal-card { width: min(100%, 520px); }


    /* PATCH: payment modal dibuat mandiri agar tidak acak dan selalu terlihat */
    #paymentModal.purchase-payment-modal {
        position: fixed !important;
        inset: 0 !important;
        z-index: 99999 !important;
        display: none !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 18px !important;
        background: rgba(15, 23, 42, 0.72) !important;
        backdrop-filter: blur(5px);
    }
    #paymentModal.purchase-payment-modal.is-open {
        display: flex !important;
    }
    #paymentModal .purchase-payment-dialog {
        position: relative !important;
        width: min(96vw, 520px) !important;
        max-height: 92vh !important;
        overflow: hidden !important;
        border-radius: 22px !important;
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 30px 80px rgba(15, 23, 42, 0.45) !important;
        animation: paymentModalPop .14s ease-out;
    }
    @keyframes paymentModalPop {
        from { opacity: 0; transform: translateY(12px) scale(.97); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    #paymentModal .purchase-payment-header {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 14px !important;
        padding: 16px 18px !important;
        background: linear-gradient(135deg, #fff7ed, #fffbeb) !important;
        border-bottom: 1px solid #fffbeb !important;
    }
    #paymentModal .purchase-payment-title-wrap {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        min-width: 0 !important;
    }
    #paymentModal .purchase-payment-icon {
        width: 42px !important;
        height: 42px !important;
        flex: 0 0 42px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        border-radius: 14px !important;
        background: #10b981 !important;
        color: #fff !important;
        box-shadow: 0 8px 16px rgba(245, 158, 11, .25) !important;
    }
    #paymentModal .purchase-payment-title {
        margin: 0 !important;
        font-size: 16px !important;
        line-height: 1.25 !important;
        font-weight: 900 !important;
        color: #0f172a !important;
    }
    #paymentModal .purchase-payment-subtitle {
        margin: 3px 0 0 !important;
        max-width: 340px !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
        font-size: 12px !important;
        color: #64748b !important;
    }
    #paymentModal .purchase-payment-close {
        width: 36px !important;
        height: 36px !important;
        flex: 0 0 36px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        border-radius: 999px !important;
        border: 1px solid #e2e8f0 !important;
        background: #ffffff !important;
        color: #64748b !important;
        cursor: pointer !important;
    }
    #paymentModal .purchase-payment-close:hover {
        background: #fffbeb !important;
        color: #b91c1c !important;
        border-color: #fffbeb !important;
    }
    #paymentModal .purchase-payment-form {
        max-height: calc(92vh - 75px) !important;
        overflow-y: auto !important;
        padding: 18px !important;
        background: #ffffff !important;
    }
    #paymentModal .purchase-payment-alert {
        border: 1px solid #ffffff !important;
        background: #ffffff !important;
        border-radius: 16px !important;
        padding: 12px 14px !important;
        margin-bottom: 14px !important;
    }
    #paymentModal .purchase-payment-alert strong {
        display: block !important;
        color: #000000 !important;
        font-size: 12px !important;
        text-transform: uppercase !important;
        letter-spacing: .04em !important;
    }
    #paymentModal .purchase-payment-alert span {
        display: block !important;
        margin-top: 3px !important;
        color: #000000 !important;
        font-size: 11px !important;
    }
    #paymentModal .purchase-payment-grid {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 12px !important;
    }
    #paymentModal .purchase-payment-field {
        margin-bottom: 13px !important;
    }
    #paymentModal .purchase-payment-field label {
        display: block !important;
        margin-bottom: 6px !important;
        color: #475569 !important;
        font-size: 11px !important;
        font-weight: 900 !important;
        text-transform: uppercase !important;
        letter-spacing: .035em !important;
    }
    #paymentModal .purchase-payment-input,
    #paymentModal .purchase-payment-textarea {
        width: 100% !important;
        border: 1px solid #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
        border-radius: 14px !important;
        padding: 11px 12px !important;
        font-size: 13px !important;
        line-height: 1.35 !important;
        outline: none !important;
        box-shadow: none !important;
    }
    #paymentModal .purchase-payment-input:focus,
    #paymentModal .purchase-payment-textarea:focus {
        border-color: #ffffff !important;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, .18) !important;
    }
    #paymentModal .purchase-payment-money-wrap {
        position: relative !important;
    }
    #paymentModal .purchase-payment-money-prefix {
        position: absolute !important;
        left: 14px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        font-size: 13px !important;
        font-weight: 900 !important;
        color: #64748b !important;
        pointer-events: none !important;
    }
    #paymentModal .purchase-payment-money {
        padding-left: 42px !important;
        padding-right: 14px !important;
        text-align: right !important;
        font-size: 18px !important;
        font-weight: 900 !important;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace !important;
    }
    #paymentModal .purchase-payment-footer {
        display: flex !important;
        justify-content: flex-end !important;
        gap: 10px !important;
        border-top: 1px solid #e2e8f0 !important;
        padding-top: 14px !important;
        margin-top: 4px !important;
    }
    #paymentModal .purchase-payment-btn {
        min-height: 40px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 7px !important;
        border-radius: 14px !important;
        padding: 0 16px !important;
        font-size: 12px !important;
        font-weight: 900 !important;
        cursor: pointer !important;
        border: 1px solid transparent !important;
    }
    #paymentModal .purchase-payment-btn.cancel {
        background: #ffffff !important;
        border-color: #cbd5e1 !important;
        color: #475569 !important;
    }
    #paymentModal .purchase-payment-btn.submit {
        background: #10b981 !important;
        border-color: #10b981 !important;
        color: #ffffff !important;
        box-shadow: 0 10px 18px rgba(217, 119, 6, .22) !important;
    }
    #paymentModal .purchase-payment-btn.submit:hover {
        background: #b45309 !important;
        border-color: #b45309 !important;
    }
    @media (max-width: 640px) {
        #paymentModal.purchase-payment-modal { padding: 12px !important; align-items: flex-end !important; }
        #paymentModal .purchase-payment-dialog { width: 100% !important; border-radius: 20px 20px 0 0 !important; }
        #paymentModal .purchase-payment-grid { grid-template-columns: 1fr !important; }
        #paymentModal .purchase-payment-footer { flex-direction: column-reverse !important; }
        #paymentModal .purchase-payment-btn { width: 100% !important; }
        #paymentModal .purchase-payment-subtitle { max-width: 220px !important; }
    }

</style>

<div class="w-full space-y-md text-on-surface">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-md border-b border-outline-variant/60 pb-md">
        <div>
            <h2 class="font-headline-xl text-headline-xl text-on-surface uppercase text-primary-emerald font-bold tracking-tight">Pembelian / Restock Barang</h2>
            <p class="text-xs text-on-surface-variant mt-0.5">Catat nota pembelian, stok masuk, status pembayaran supplier, hutang, dan invoice pembelian.</p>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-2 self-end sm:self-auto">
            <form method="GET" action="{{ route('purchase.index') }}" class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">search</span>
                <input type="text" name="search" value="{{ $search }}" class="w-64 max-w-full bg-surface-container-low border border-outline-variant rounded-full pl-9 pr-4 py-2 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all" placeholder="Cari invoice, barang, supplier, keterangan...">
            </form>
            @if ($search !== '')
                <a href="{{ route('purchase.index') }}" class="flex h-11 items-center gap-1 rounded-xl border border-outline-variant bg-white px-3 text-xs font-bold text-on-surface-variant hover:bg-surface-container-low">
                    <span class="material-symbols-outlined text-lg">close</span>Reset
                </a>
            @endif
            <a href="{{ route('purchase.export_excel', request()->query()) }}" class="flex h-11 items-center gap-1.5 rounded-xl border border-primary-emerald bg-white px-4 text-sm font-bold text-primary-emerald shadow-sm transition-all hover:bg-primary-emerald/5">
                <span class="material-symbols-outlined text-lg">file_export</span>
                <span>Export Excel</span>
            </a>
            <button type="button" class="flex items-center gap-1.5 bg-primary-emerald text-on-primary rounded-xl px-4 py-2 shadow-md transition-all active:scale-95 text-sm font-bold cursor-pointer h-11 hover:brightness-110" onclick="openPurchaseModal()">
                <span class="material-symbols-outlined text-lg">add</span>
                <span>Nota Baru</span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="rounded-xl border border-outline-variant bg-white p-4 shadow-sm">
            <p class="text-[11px] font-black uppercase tracking-wider text-on-surface-variant">Total Pembelian</p>
            <p class="mt-1 text-lg font-black text-on-surface">Rp {{ number_format((int) $purchaseSummary['total_harga'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-outline-variant bg-white p-4 shadow-sm">
            <p class="text-[11px] font-black uppercase tracking-wider text-on-surface-variant">Sudah Dibayar</p>
            <p class="mt-1 text-lg font-black text-primary-emerald">Rp {{ number_format((int) $purchaseSummary['total_dibayar'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-outline-variant bg-white p-4 shadow-sm">
            <p class="text-[11px] font-black uppercase tracking-wider text-on-surface-variant">Sisa Hutang Supplier</p>
            <p class="mt-1 text-lg font-black text-amber-700">Rp {{ number_format((int) $purchaseSummary['total_sisa'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-outline-variant bg-white p-4 shadow-sm">
            <p class="text-[11px] font-black uppercase tracking-wider text-on-surface-variant">Nota / Qty</p>
            <p class="mt-1 text-lg font-black text-on-surface">{{ number_format((int) $purchaseSummary['total_nota'], 0, ',', '.') }} Nota / {{ format_qty($purchaseSummary['total_qty']) }} Qty</p>
        </div>
    </div>

    <div class="bg-white border border-outline-variant rounded-xl overflow-visible shadow-sm">
    <div class="overflow-x-auto custom-scrollbar overflow-visible">
        <table class="w-full min-w-[1450px] text-left border-collapse text-sm">
            <thead>
                <tr class="bg-surface-container-low border-b border-outline-variant text-on-surface-variant font-bold">
                    <th class="px-3 py-2 w-12 text-center">No</th>
                    <th class="px-3 py-2 whitespace-nowrap">NO INVOICE</th>
                    <th class="px-3 py-2 whitespace-nowrap">TANGGAL</th>
                    <th class="px-3 py-2">SUPPLIER</th>
                    <!-- <th class="px-3 py-2 min-w-[220px]">KET. PEMBELIAN</th> -->
                    <th class="px-3 py-2 whitespace-nowrap">STOK MASUK</th>
                    <th class="px-3 py-2 text-left">TOTAL</th>
                    <th class="px-3 py-2 text-left">DIBAYAR</th>
                    <th class="px-3 py-2 text-left">SISA / HUTANG</th>
                    <th class="px-3 py-2 text-center">STATUS</th>
                    <th class="px-3 py-2 text-center w-16">AKSI</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/40">
                @forelse ($purchases as $key => $item)
                    @php $statusBayar = $item->payment_status ?? 'Belum Dibayar'; @endphp
                    <tr class="hover:bg-surface-container-low/50 transition-colors">
                        {{-- No --}}
                        <td class="px-3 py-2 text-center text-on-surface-variant">{{ $purchases->firstItem() + $key }}</td>
                        
                        {{-- Nomor invoice pembelian --}}
                        <td class="px-3 py-2 font-mono text-xs font-bold text-primary-emerald whitespace-nowrap">{{ $item->no_invoice }}</td>

                        {{-- Tanggal --}}
                        <td class="px-3 py-2 text-on-surface-variant whitespace-nowrap">{{ \Carbon\Carbon::parse($item->tgl_pembelian)->format('d M Y') }}</td>
                        
                        {{-- Supplier --}}
                        <td class="px-3 py-2 font-semibold">{{ $item->nama_supplier ?? 'Supplier tidak ditemukan' }}</td>

                        {{-- Keterangan pembelian --}}
                        <!-- <td class="px-3 py-2 text-xs text-on-surface-variant" title="{{ $item->note ?: '-' }}">
                            <span class="block max-w-[260px] truncate">{{ $item->note ?: '-' }}</span>
                        </td> -->

                        {{-- Ringkasan stok yang masuk dari nota --}}
                        <td class="px-3 py-2 text-xs font-semibold text-on-surface whitespace-nowrap">{{ $item->total_item ?: '-' }}</td>
                        
                        {{-- Nominal Rupiah (Text Right) --}}
                        <td class="px-3 py-2 font-bold text-left text-on-surface whitespace-nowrap">Rp {{ number_format((int) $item->total_harga, 0, ',', '.') }}</td>
                        <td class="px-3 py-2 font-bold text-left text-primary-emerald whitespace-nowrap">Rp {{ number_format((int) $item->paid_total, 0, ',', '.') }}</td>
                        <td class="px-3 py-2 font-bold text-left text-amber-700 whitespace-nowrap">Rp {{ number_format((int) $item->remaining_total, 0, ',', '.') }}</td>
                        
                        {{-- Status Badge --}}
                        <td class="px-3 py-2 text-center whitespace-nowrap">
                            <span class="px-2 py-0.5 rounded {{ $paymentBadge($statusBayar) }} text-[10px] font-bold uppercase tracking-wider">{{ $statusBayar }}</span>
                        </td>
                        
                        
            <td class="px-3 py-2 text-center overflow-visible">
                <div class="relative inline-block text-left">
                    <details class="group">
                        <summary class="list-none [&::-webkit-details-marker]:hidden flex h-6 w-6 items-center justify-center rounded border border-outline-variant bg-surface hover:bg-surface-container-high text-on-surface-variant cursor-pointer select-none transition-all shadow-sm">
                            <span class="material-symbols-outlined text-[16px] group-open:rotate-180 transition-transform duration-200">
                                more_vert
                            </span>
                        </summary>
            <div class="absolute right-0 mt-1 w-36 origin-top-right rounded-md border border-outline-variant bg-white p-1 shadow-xl z-50 flex flex-col gap-0.5 animate-fadeIn">
                
                {{-- Tombol Detail (Ditambahkan cursor-pointer) --}}
                <button type="button" class="flex w-full items-center gap-2 rounded px-2.5 py-1.5 text-left text-xs font-semibold text-emerald-600 hover:bg-emerald-50 cursor-pointer select-none transition-colors" onclick="openPurchaseDetail({{ $item->id }})">
                    <span class="material-symbols-outlined text-[14px]">visibility</span><span>Detail</span>
                </button>

                {{-- Tombol Edit (Ditambahkan cursor-pointer) --}}
                <button type="button" class="flex w-full items-center gap-2 rounded px-2.5 py-1.5 text-left text-xs font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer select-none transition-colors" onclick="openPurchaseEdit({{ $item->id }})">
                    <span class="material-symbols-outlined text-[14px]">edit</span><span>Edit</span>
                </button>

                {{-- Tombol Bayar (Ditambahkan cursor-pointer) --}}
                @if (($item->payment_status ?? '') !== 'Lunas')
                    <button type="button" class="flex w-full items-center gap-2 rounded px-2.5 py-1.5 text-left text-xs font-semibold text-amber-600 hover:bg-amber-50 cursor-pointer select-none transition-colors border-t border-outline-variant/30 mt-0.5 pt-1.5" onclick="openPaymentModal({{ $item->id }}, '{{ addslashes($item->no_invoice) }}', {{ (int) $item->remaining_total }})">
                        <span class="material-symbols-outlined text-[14px]">payments</span><span>Bayar</span>
                    </button>
                @endif
            </div>
        </details>
    </div>
</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-4 py-8 text-center text-on-surface-variant italic">Belum ada data pembelian/restock untuk filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="bg-surface-container-low border-t-2 border-outline-variant text-right font-black text-on-surface">
                    <td colspan="6" class="px-3 py-2 text-left uppercase tracking-wider">Total Hasil</td>
                    <td class="px-3 py-2 whitespace-nowrap">Rp {{ number_format((int) $purchaseSummary['total_harga'], 0, ',', '.') }}</td>
                    <td class="px-3 py-2 text-primary-emerald whitespace-nowrap">Rp {{ number_format((int) $purchaseSummary['total_dibayar'], 0, ',', '.') }}</td>
                    <td class="px-3 py-2 text-amber-700 whitespace-nowrap">Rp {{ number_format((int) $purchaseSummary['total_sisa'], 0, ',', '.') }}</td>
                    <td colspan="2" class="px-3 py-2 text-center text-[11px] font-bold text-on-surface-variant whitespace-nowrap">
                        {{ number_format((int) $purchaseSummary['total_baris_item'], 0, ',', '.') }} Baris
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="px-4 py-2 border-t border-outline-variant bg-surface-container-lowest">{{ $purchases->links() }}</div>
</div>
</div>
@endsection

@push('modals')
<div class="fixed inset-0 z-50 flex items-center justify-center p-md modal-overlay overflow-y-auto hidden" id="purchaseModal">
    <div class="bg-white w-full max-w-5xl rounded-2xl shadow-2xl animate-modal border border-outline-variant overflow-hidden flex flex-col my-auto max-h-[95vh]">
        <div class="px-6 py-4 border-b border-outline-variant flex items-center justify-between bg-white shrink-0">
            <div class="text-left">
                <h3 id="purchaseModalTitle" class="font-headline-sm text-headline-sm font-bold text-on-surface">Input Nota Pembelian Supplier</h3>
                <p id="purchaseModalSubtitle" class="text-xs text-on-surface-variant mt-0.5">Restock barang, invoice pembelian, pembayaran supplier, dan keterangan opsional.</p>
            </div>
            <button type="button" class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-surface-container-high transition-colors text-on-surface-variant cursor-pointer shrink-0" onclick="closePurchaseModal()"><span class="material-symbols-outlined">close</span></button>
        </div>

        <form id="purchaseForm" method="POST" action="{{ route('purchase.store') }}" data-store-action="{{ route('purchase.store') }}" class="flex-1 flex flex-col min-h-0 overflow-hidden">
            @csrf
            <span id="purchaseFormMethod"></span>
            <div class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant"><p class="text-xs font-bold text-on-surface-variant uppercase">Total Qty</p><p class="text-xl font-black text-on-surface mt-0.5"><span id="summary-total-qty">0</span> <span class="text-xs font-normal text-on-surface-variant">Pcs</span></p></div>
                    <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant"><p class="text-xs font-bold text-on-surface-variant uppercase">Jenis Barang</p><p class="text-xl font-black text-on-surface mt-0.5"><span id="summary-jenis-barang">0</span> <span class="text-xs font-normal text-on-surface-variant">Jenis</span></p></div>
                    <div class="bg-primary-emerald p-4 rounded-xl border border-primary-emerald text-on-primary shadow-md"><p class="text-xs font-bold uppercase opacity-80">Grand Total Purchase</p><p class="text-2xl font-black tracking-tight" id="summary-grand-total">Rp 0</p></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-1.5"><label class="font-semibold text-xs text-on-surface-variant uppercase tracking-wider">Invoice System No</label><input class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-4 py-2.5 font-mono text-on-surface cursor-not-allowed outline-none text-sm" name="display_no_invoice" id="display_no_invoice" readonly type="text" value="{{ $nextInvoice }}"/></div>
                    <div class="space-y-1.5"><label class="font-semibold text-xs text-on-surface-variant uppercase tracking-wider">Tanggal Beli</label><input class="w-full border border-outline-variant rounded-xl px-4 py-2.5 font-body-md text-on-surface focus:ring-2 focus:ring-primary-emerald/10 focus:border-primary-emerald outline-none transition-all text-sm bg-white" name="tgl_pembelian" value="{{ now()->toDateString() }}" required type="date"/></div>
                    <div class="space-y-1.5 relative dropdown-container-supplier">
                        <label class="font-semibold text-xs text-on-surface-variant uppercase tracking-wider">Supplier Mitra</label>
                        <input type="hidden" name="supplier_id" id="supplier_id_hidden" required>
                        <div class="relative w-full">
                            <input 
                                type="text" 
                                id="supplier_search_input" 
                                class="w-full border border-outline-variant rounded-xl px-4 py-2.5 font-body-md text-on-surface focus:ring-2 focus:ring-primary-emerald/10 focus:border-primary-emerald outline-none transition-all text-sm bg-white" 
                                placeholder="Ketik untuk mencari supplier..." 
                                autocomplete="off"
                                required
                            >
                            <div id="supplier_dropdown_list" class="dropdown-list hidden custom-scrollbar"></div>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <h4 class="font-semibold text-xs text-on-surface-variant uppercase tracking-wider">Pilih Produk</h4>
                    <div class="border border-outline-variant rounded-xl overflow-visible bg-white shadow-sm">
                        <table class="w-full text-left border-collapse text-xs" id="products-table">
                            <thead><tr class="bg-surface-container-low border-b border-outline-variant text-on-surface-variant font-bold"><th class="px-4 py-3 w-12 text-center">NO</th><th class="px-4 py-3 min-w-[280px]">NAMA PRODUK / BARANG</th><th class="px-4 py-3 w-28 text-center">QTY</th><th class="px-4 py-3 w-24 text-center">SATUAN</th><th class="px-4 py-3 text-right w-40">HARGA BELI</th><th class="px-4 py-3 text-right w-44">SUBTOTAL</th><th class="px-4 py-3 w-12 text-center"></th></tr></thead>
                            <tbody class="divide-y divide-outline-variant" id="table-body"></tbody>
                        </table>
                        <div class="p-3 bg-surface-container-low border-t border-outline-variant"><button type="button" class="flex items-center gap-1 text-primary-emerald font-bold hover:bg-primary-emerald/5 px-3 py-1.5 rounded-lg transition-all active:scale-95 text-xs cursor-pointer" id="add-row-btn"><span class="material-symbols-outlined text-[18px]">add_circle</span><span>Tambah Produk</span></button></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="font-semibold text-xs text-on-surface-variant uppercase tracking-wider">Keterangan Pembelian <span class="font-normal normal-case">(opsional)</span></label>
                        <textarea name="note" id="purchase_note" rows="4" class="w-full border border-outline-variant rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-emerald/10 focus:border-primary-emerald outline-none" placeholder="Contoh: barang diterima lengkap, harga termasuk ongkir, pembelian urgent..."></textarea>
                    </div>
                    <div id="initialPaymentBox" class="space-y-3 rounded-xl border border-outline-variant bg-surface-container-low/40 p-4">
                        <h4 class="font-semibold text-xs text-on-surface-variant uppercase tracking-wider">Pembayaran Awal <span class="font-normal normal-case">(opsional)</span></h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div><label class="block text-[11px] font-bold text-on-surface-variant mb-1">Nominal</label><input type="text" name="initial_payment" id="initial_payment" value="0" class="money-input w-full border border-outline-variant rounded-xl px-4 py-2.5 text-sm text-right font-mono" inputmode="numeric"></div>
                            <div><label class="block text-[11px] font-bold text-on-surface-variant mb-1">Tanggal</label><input type="date" name="initial_payment_date" value="{{ now()->toDateString() }}" class="w-full border border-outline-variant rounded-xl px-4 py-2.5 text-sm"></div>
                            <div><label class="block text-[11px] font-bold text-on-surface-variant mb-1">Metode</label><input type="text" name="initial_payment_method" value="Tunai" class="w-full border border-outline-variant rounded-xl px-4 py-2.5 text-sm" placeholder="Tunai / Transfer / Giro"></div>
                            <div><label class="block text-[11px] font-bold text-on-surface-variant mb-1">Catatan Bayar</label><input type="text" name="initial_payment_note" class="w-full border border-outline-variant rounded-xl px-4 py-2.5 text-sm" placeholder="Contoh: DP awal"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-outline-variant bg-white flex items-center justify-end gap-3 shrink-0">
                <button type="button" class="px-6 py-2.5 rounded-lg border border-outline-variant text-on-surface-variant font-semibold text-sm hover:bg-surface-container-high transition-all active:scale-95 cursor-pointer" onclick="closePurchaseModal()">Batal</button>
                <button type="submit" class="px-6 py-2.5 rounded-lg bg-primary-emerald text-on-primary font-semibold text-sm shadow-md hover:brightness-110 transition-all active:scale-95 flex items-center gap-2 cursor-pointer"><span class="material-symbols-outlined text-[18px]">save</span><span id="purchaseSubmitLabel">Simpan Nota</span></button>
            </div>
        </form>
    </div>
</div>

<div class="fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-black/50 backdrop-blur-sm" id="confirmPurchaseModal">
    <div class="w-full max-w-sm bg-white rounded-xl border border-outline-variant shadow-2xl overflow-hidden text-sm animate-modal">
        <div class="p-4 border-b border-outline-variant"><h4 class="font-bold text-on-surface leading-tight">Konfirmasi Nota Pembelian</h4><p class="text-xs text-on-surface-variant">Validasi data restock dan pembayaran awal.</p></div>
        <div class="p-4 space-y-3"><p class="text-on-surface text-xs">Apakah data invoice pembelian supplier ini sudah sesuai?</p><div class="rounded-lg bg-surface-container-low p-3 space-y-1.5 text-xs"><div class="flex justify-between text-on-surface-variant"><span>Total Qty Masuk</span><strong id="confirmTotalItem" class="text-on-surface">0 Qty</strong></div><div class="flex justify-between text-on-surface-variant"><span>Grand Total</span><strong id="confirmGrandTotal" class="text-primary-emerald font-bold">Rp 0</strong></div><div class="flex justify-between text-on-surface-variant"><span>Pembayaran Awal</span><strong id="confirmInitialPayment" class="text-amber-700 font-bold">Rp 0</strong></div></div></div>
        <div class="p-3 bg-surface-container-low flex justify-end gap-2 border-t border-outline-variant/60"><button type="button" class="px-3 py-1.5 text-xs rounded-lg border border-outline-variant font-bold hover:bg-surface-container-high transition-colors text-on-surface-variant cursor-pointer" onclick="hideConfirmPurchaseModal()">Periksa Kembali</button><button type="button" class="px-3 py-1.5 text-xs rounded-lg bg-primary-emerald text-on-primary font-bold active:scale-95 transition-all cursor-pointer" id="executePurchaseSubmitBtn">Ya, Simpan</button></div>
    </div>
</div>

<div id="purchaseDetailModal" class="fixed inset-0 z-[70] hidden items-center justify-center p-4 bg-black/40 backdrop-blur-sm" role="dialog" aria-modal="true">
    <div class="relative z-10 flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl border border-outline-variant bg-white shadow-2xl animate-modal">
        <div class="flex items-center justify-between border-b border-outline-variant bg-white px-6 py-4"><div><h3 id="purchaseDetailTitle" class="text-base font-bold text-on-surface">Detail Nota Pembelian</h3><p id="purchaseDetailSubtitle" class="text-xs text-on-surface-variant mt-0.5">Memuat data pembelian...</p></div><button type="button" class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-error/10 hover:text-error transition-colors cursor-pointer" onclick="closePurchaseDetail()"><span class="material-symbols-outlined">close</span></button></div>
        <div class="min-h-0 flex-1 overflow-y-auto p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-surface-container-low/50 border border-outline-variant/60 rounded-xl p-4 text-xs">
                <div class="space-y-2.5"><div><span class="text-on-surface-variant font-medium">No. Invoice</span><strong id="purchaseDetailInvoice" class="block text-primary-emerald font-mono text-sm">-</strong></div><div><span class="text-on-surface-variant font-medium">Tanggal</span><span id="purchaseDetailDate" class="block text-on-surface font-semibold">-</span></div><div><span class="text-on-surface-variant font-medium">Status Bayar</span><span id="purchaseDetailStatus" class="block mt-1">-</span></div></div>
                <div class="space-y-2.5"><div><span class="text-on-surface-variant font-medium">Supplier</span><span id="purchaseDetailSupplier" class="block text-on-surface font-bold">-</span></div><div><span class="text-on-surface-variant font-medium">PIC / Kontak</span><span id="purchaseDetailPic" class="block text-on-surface">-</span></div><div><span class="text-on-surface-variant font-medium">Alamat</span><span id="purchaseDetailAddress" class="block text-on-surface-variant">-</span></div></div>
                <div class="space-y-2.5"><div><span class="text-on-surface-variant font-medium">Total Pembelian</span><strong id="purchaseDetailGrandTotal" class="block text-on-surface text-sm">Rp 0</strong></div><div><span class="text-on-surface-variant font-medium">Sudah Dibayar</span><strong id="purchaseDetailPaidTotal" class="block text-primary-emerald text-sm">Rp 0</strong></div><div><span class="text-on-surface-variant font-medium">Sisa Hutang</span><strong id="purchaseDetailRemaining" class="block text-amber-700 text-sm">Rp 0</strong></div></div>
            </div>
            <div id="purchaseDetailNoteBox" class="hidden rounded-xl border border-outline-variant bg-white p-4 text-xs"><p class="font-bold uppercase tracking-wider text-on-surface-variant mb-1">Keterangan Pembelian</p><p id="purchaseDetailNote" class="text-on-surface whitespace-pre-line"></p></div>
            <div class="border border-outline-variant rounded-xl overflow-hidden shadow-sm bg-white"><div class="bg-surface-container-low border-b border-outline-variant px-4 py-2.5"><span class="text-xs font-bold text-on-surface uppercase tracking-wider">Rincian Barang Masuk</span></div><div class="overflow-x-auto"><table class="w-full text-left border-collapse text-xs"><thead><tr class="bg-surface-container-low/60 border-b border-outline-variant text-on-surface-variant font-bold"><th class="w-12 py-2 px-3 text-center">No</th><th class="py-2 px-3">Barang</th><th class="w-24 py-2 px-3 text-center">Qty</th><th class="w-24 py-2 px-3 text-center">Satuan</th><th class="w-36 py-2 px-3 text-right">Harga</th><th class="w-40 py-2 px-3 text-right">Subtotal</th></tr></thead><tbody id="purchaseDetailRows" class="divide-y divide-outline-variant/40"></tbody></table></div></div>
            <div class="border border-outline-variant rounded-xl overflow-hidden shadow-sm bg-white"><div class="bg-surface-container-low border-b border-outline-variant px-4 py-2.5"><span class="text-xs font-bold text-on-surface uppercase tracking-wider">Riwayat Pembayaran Supplier</span></div><div class="overflow-x-auto"><table class="w-full text-left border-collapse text-xs"><thead><tr class="bg-surface-container-low/60 border-b border-outline-variant text-on-surface-variant font-bold"><th class="w-12 py-2 px-3 text-center">No</th><th class="py-2 px-3">Tanggal</th><th class="py-2 px-3">Metode</th><th class="py-2 px-3">Catatan</th><th class="w-40 py-2 px-3 text-right">Nominal</th></tr></thead><tbody id="purchasePaymentRows" class="divide-y divide-outline-variant/40"></tbody></table></div></div>
        </div>
        <div class="flex justify-end gap-2 border-t border-outline-variant bg-surface-container-low/40 px-6 py-3"><a id="purchaseDetailInvoiceLink" href="#" target="_blank" class="inline-flex h-9 items-center justify-center rounded-xl bg-primary-emerald px-5 text-xs font-bold text-on-primary hover:brightness-110 transition-colors shadow-sm cursor-pointer">Cetak Invoice</a><button type="button" class="inline-flex h-9 items-center justify-center rounded-xl border border-outline-variant bg-white px-5 text-xs font-bold text-on-surface-variant hover:bg-surface-container-high transition-colors shadow-sm cursor-pointer" onclick="closePurchaseDetail()">Tutup</button></div>
    </div>
</div>

<div id="paymentModal" class="purchase-payment-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="paymentModalTitle">
    <div class="purchase-payment-dialog payment-modal-card" data-payment-dialog>
        <div class="purchase-payment-header">
            <div class="purchase-payment-title-wrap">
                <div class="purchase-payment-icon">
                    <span class="material-symbols-outlined">payments</span>
                </div>
                <div class="min-w-0">
                    <h3 id="paymentModalTitle" class="purchase-payment-title">Tambah Pembayaran Supplier</h3>
                    <p id="paymentModalSubtitle" class="purchase-payment-subtitle">-</p>
                </div>
            </div>
            <button type="button" onclick="closePaymentModal()" class="purchase-payment-close" aria-label="Tutup modal pembayaran">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="paymentForm" method="POST" action="#" class="purchase-payment-form">
            @csrf

            <div class="purchase-payment-alert">
                <strong id="paymentRemainingHint">Sisa hutang: Rp 0</strong>
                <span>Nominal pembayaran tidak boleh melebihi sisa hutang supplier.</span>
            </div>

            <div class="purchase-payment-grid">
                <div class="purchase-payment-field">
                    <label for="paymentDate">Tanggal Bayar</label>
                    <input id="paymentDate" type="date" name="payment_date" value="{{ now()->toDateString() }}" required class="purchase-payment-input">
                </div>
                <div class="purchase-payment-field">
                    <label for="paymentMethod">Metode</label>
                    <input id="paymentMethod" type="text" name="payment_method" value="Tunai" class="purchase-payment-input" placeholder="Tunai / Transfer / Giro">
                </div>
            </div>

            <div class="purchase-payment-field">
                <label for="paymentAmount">Nominal Pembayaran</label>
                <div class="purchase-payment-money-wrap">
                    <span class="purchase-payment-money-prefix">Rp</span>
                    <input type="text" name="amount" id="paymentAmount" required inputmode="numeric" class="money-input purchase-payment-input purchase-payment-money" autocomplete="off">
                </div>
            </div>

            <div class="purchase-payment-field">
                <label for="paymentNote">Keterangan <span style="font-weight:600;text-transform:none;color:#94a3b8;">(opsional)</span></label>
                <textarea id="paymentNote" name="payment_note" rows="3" class="purchase-payment-textarea" placeholder="Contoh: DP, pelunasan, transfer bank..."></textarea>
            </div>

            <div class="purchase-payment-footer">
                <button type="button" onclick="closePaymentModal()" class="purchase-payment-btn cancel">Batal</button>
                <button type="submit" class="purchase-payment-btn submit">
                    <span class="material-symbols-outlined" style="font-size:18px;">save</span>
                    <span>Simpan Pembayaran</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endpush

@push('scripts')
<script>
    const purchaseSuppliers = @json($suppliers);
    const purchaseProducts = @json($products);
    const purchaseProductIndex = Array.isArray(purchaseProducts) ? purchaseProducts.map(product => Object.assign({}, product, {_search: String((product.nama_barang || '') + ' ' + (product.kode_barang || '')).toLowerCase()})) : [];
    const purchaseModal = document.getElementById('purchaseModal');
    const confirmPurchaseModal = document.getElementById('confirmPurchaseModal');
    const tableBody = document.getElementById('table-body');
    const addRowBtn = document.getElementById('add-row-btn');
    const purchaseForm = document.getElementById('purchaseForm');
    const purchaseDetailBaseUrl = @json(url('/purchase'));
    const storeAction = @json(route('purchase.store'));
    const nextInvoice = @json($nextInvoice);
    const today = @json(now()->toDateString());
    let purchaseAllowSubmit = false;
    let formMode = 'create';

    function initSupplierAutocomplete() {
        const hiddenInput = document.getElementById('supplier_id_hidden');
        const searchInput = document.getElementById('supplier_search_input');
        const dropdown = document.getElementById('supplier_dropdown_list');

        if (!searchInput || !dropdown || !hiddenInput) return;

        function renderSupplierDropdown(filter = '') {
            const query = String(filter || '').toLowerCase().trim();
            const rawData = Array.isArray(purchaseSuppliers) ? purchaseSuppliers : [];
            const selectedName = String(searchInput.dataset.selectedName || '').toLowerCase().trim();

            let filtered;
            if (!query || (selectedName && query === selectedName)) {
                filtered = rawData;
            } else {
                filtered = rawData.filter(s => 
                    String(s.nama_supplier || '').toLowerCase().includes(query) || String(s.kode_supplier || '').toLowerCase().includes(query)
                );
            }

            if (!filtered.length) {
                dropdown.innerHTML = '<div class="px-4 py-3 text-xs font-semibold text-on-surface-variant">Supplier tidak ditemukan.</div>';
                dropdown.classList.remove('hidden');
                return;
            }

            dropdown.innerHTML = filtered.map(s => `
                <button type="button" class="block w-full px-4 py-2 text-left hover:bg-surface-container-low cursor-pointer border-b border-outline-variant/30 transition-colors" data-id="${s.id}" data-name="${escapeAttr(s.nama_supplier || '')}">
                    <div class="font-semibold text-on-surface text-xs">${escapeHtml(s.nama_supplier || '-')}</div>
                </button>
            `).join('');
            dropdown.classList.remove('hidden');
        }

        searchInput.addEventListener('focus', () => {
            try { searchInput.select(); } catch(e) {}
            renderSupplierDropdown('');
        });
        searchInput.addEventListener('click', () => {
            renderSupplierDropdown('');
        });
        searchInput.addEventListener('input', (e) => {
            hiddenInput.value = '';
            searchInput.dataset.selectedName = '';
            renderSupplierDropdown(e.target.value);
        });

        dropdown.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-id]');
            if (!btn) return;
            hiddenInput.value = btn.dataset.id;
            searchInput.value = btn.dataset.name;
            searchInput.dataset.selectedName = btn.dataset.name;
            dropdown.classList.add('hidden');
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown-container-supplier')) {
                dropdown.classList.add('hidden');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', initSupplierAutocomplete);

    function formatNumber(num) { return new Intl.NumberFormat('id-ID').format(Number(num || 0)); }
    function formatRupiah(value) { const cleaned = String(value || 0).replace(/\./g, '').replace(/,/g, '.').replace(/[^0-9.-]/g, ''); return 'Rp ' + formatNumber(Number(cleaned) || 0); }
    function parseNumber(str) { return parseFloat(String(str || 0).replace(/\./g, '').replace(/,/g, '.').replace(/[^0-9.-]/g, '')) || 0; }
    function escapeHtml(value) { return String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c])); }
    function escapeAttr(value) { return escapeHtml(value).replace(/`/g, '&#096;'); }

    function updateSummary() {
        let totalQty = 0, totalJenis = 0, grandTotal = 0;
        tableBody.querySelectorAll('tr').forEach(row => {
            const qty = parseNumber(row.querySelector('.row-qty')?.value || 0);
            const price = parseNumber(row.querySelector('.row-price')?.value || 0);
            const subtotal = qty * price;
            row.querySelector('.row-subtotal').textContent = formatNumber(subtotal);
            if (qty > 0 || row.querySelector('.row-name').value !== '') { totalQty += qty; totalJenis++; grandTotal += subtotal; }
        });
        document.getElementById('summary-total-qty').textContent = totalQty;
        document.getElementById('summary-jenis-barang').textContent = totalJenis;
        document.getElementById('summary-grand-total').textContent = formatRupiah(grandTotal);
        document.getElementById('confirmTotalItem').innerText = totalQty + ' Qty';
        document.getElementById('confirmGrandTotal').innerText = formatRupiah(grandTotal);
        document.getElementById('confirmInitialPayment').innerText = formatRupiah(document.getElementById('initial_payment')?.value || 0);
        return {totalQty, totalJenis, grandTotal};
    }

    function createRow(initialData = null) {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-surface-container-low/40 transition-colors group';
        tr.innerHTML = `
            <td class="px-4 py-3 text-center text-slate-400 font-mono text-xs row-number w-12">00</td>
            <td class="px-4 py-3 relative dropdown-container"><div class="relative w-full"><input class="row-name w-full border border-outline-variant rounded-lg pl-3 pr-8 py-1.5 font-body-md text-on-surface focus:ring-2 focus:ring-primary-emerald/10 focus:border-primary-emerald outline-none transition-all text-xs bg-white" placeholder="Ketik nama atau kode barang..." type="text" value="${escapeAttr(initialData?.name || '')}" autocomplete="off"><div class="dropdown-list hidden custom-scrollbar"></div></div></td>
            <td class="px-4 py-3 text-center w-28 min-w-[112px]"><input class="row-qty w-full bg-transparent border-none focus:ring-0 text-center font-mono text-xs p-0 outline-none font-bold text-on-surface" type="text" inputmode="decimal" name="qty[]" value="${initialData?.qty || 1}" oninput="this.value = this.value.replace(/[^0-9.,]/g, '')" autocomplete="off"></td>
            <td class="px-4 py-3 text-center w-24 min-w-[96px]"><input type="text" name="satuan[]" class="row-unit w-full bg-transparent border-none focus:ring-0 text-center font-semibold text-xs text-on-surface-variant outline-none cursor-not-allowed p-0" readonly value="${escapeAttr(initialData?.unit || '-')}"></td>
            <td class="px-4 py-3 text-right w-40 min-w-[160px]"><input class="row-price money-input w-full bg-transparent border-none focus:ring-0 text-right font-mono text-xs p-0 outline-none font-semibold text-on-surface" type="text" name="harga_beli[]" value="${initialData ? formatNumber(initialData.price) : '0'}" autocomplete="off"></td>
            <td class="px-4 py-3 text-right font-mono text-on-surface text-xs row-subtotal w-44 min-w-[176px]">0</td>
            <td class="px-4 py-3 text-center w-12"><input type="hidden" name="kode_barang[]" class="row-sku-hidden" value="${escapeAttr(initialData?.sku || '')}"><button type="button" class="delete-row-btn text-error/80 hover:text-error hover:bg-error/10 p-1.5 rounded-full transition-colors cursor-pointer" title="Hapus Baris"><span class="material-symbols-outlined text-base">delete</span></button></td>`;
        const nameInput = tr.querySelector('.row-name');
        const qtyInput = tr.querySelector('.row-qty');
        const priceInput = tr.querySelector('.row-price');
        const unitInput = tr.querySelector('.row-unit');
        const skuHidden = tr.querySelector('.row-sku-hidden');
        const dropdown = tr.querySelector('.dropdown-list');
        const showDropdown = (filter = '') => {
            const query = String(filter || '').toLowerCase().trim();
            const filtered = [];
            for (let i = 0; i < purchaseProductIndex.length; i++) {
                const product = purchaseProductIndex[i];
                if (!query || String(product._search || '').includes(query)) { filtered.push(product); if (filtered.length >= 15) break; }
            }
            if (!filtered.length) { dropdown.innerHTML = '<div class="px-4 py-3 text-xs font-semibold text-on-surface-variant">Barang tidak ditemukan.</div>'; dropdown.classList.remove('hidden'); return; }
            dropdown.innerHTML = filtered.map(p => `<button type="button" class="block w-full px-4 py-2 text-left hover:bg-surface-container-low cursor-pointer border-b border-outline-variant/30 transition-colors" data-name="${escapeAttr(p.nama_barang || '')}" data-price="${Number(p.harga_beli_terakhir || 0)}" data-unit="${escapeAttr(p.satuan || 'Unit')}" data-sku="${escapeAttr(p.kode_barang || '')}"><div class="font-semibold text-on-surface text-xs">${escapeHtml(p.nama_barang || '-')}</div><div class="text-[10px] text-primary-emerald font-mono mt-0.5">SKU: ${escapeHtml(p.kode_barang || '-')} · Stok: ${Number(p.sisa_stok || 0).toLocaleString('id-ID')}</div></button>`).join('');
            dropdown.classList.remove('hidden');
        };
        nameInput.addEventListener('focus', () => showDropdown(nameInput.value));
        nameInput.addEventListener('input', e => { skuHidden.value = ''; unitInput.value = '-'; showDropdown(e.target.value); updateSummary(); });
        dropdown.addEventListener('click', e => {
            const item = e.target.closest('[data-name]');
            if (!item) return;
            nameInput.value = item.dataset.name; priceInput.value = formatNumber(item.dataset.price); unitInput.value = item.dataset.unit; skuHidden.value = item.dataset.sku; dropdown.classList.add('hidden'); qtyInput.focus(); updateSummary();
        });
        qtyInput.addEventListener('input', updateSummary);
        priceInput.addEventListener('input', e => { e.target.value = formatNumber(String(e.target.value).replace(/\D/g, '')); updateSummary(); });
        tr.querySelector('.delete-row-btn').addEventListener('click', () => { tr.remove(); reorderRows(); updateSummary(); });
        tableBody.appendChild(tr); reorderRows(); updateSummary();
    }
    function reorderRows() { Array.from(tableBody.rows).forEach((row, idx) => { row.querySelector('.row-number').textContent = (idx + 1).toString().padStart(2, '0'); }); }
    addRowBtn.addEventListener('click', () => createRow());
    document.addEventListener('click', e => { if (!e.target.closest('.dropdown-container')) tableBody.querySelectorAll('.dropdown-list:not(.hidden)').forEach(d => d.classList.add('hidden')); });
    document.querySelectorAll('.money-input').forEach(input => input.addEventListener('input', e => { e.target.value = formatNumber(String(e.target.value).replace(/\D/g, '')); updateSummary(); }));

    function setMode(mode, data = null) {
        formMode = mode; purchaseAllowSubmit = false;
        document.getElementById('purchaseFormMethod').innerHTML = mode === 'edit' ? '<input type="hidden" name="_method" value="PUT">' : '';
        purchaseForm.action = mode === 'edit' && data ? `${purchaseDetailBaseUrl}/${data.id}` : storeAction;
        document.getElementById('purchaseModalTitle').textContent = mode === 'edit' ? 'Edit Nota Pembelian Supplier' : 'Input Nota Pembelian Supplier';
        document.getElementById('purchaseModalSubtitle').textContent = mode === 'edit' ? 'Edit nota akan menyesuaikan stok lama dan stok baru otomatis. Pembayaran dicatat lewat tombol Bayar.' : 'Restock barang, invoice pembelian, pembayaran supplier, dan keterangan opsional.';
        document.getElementById('purchaseSubmitLabel').textContent = mode === 'edit' ? 'Update Nota' : 'Simpan Nota';
        document.getElementById('display_no_invoice').value = data?.no_invoice || nextInvoice;
        document.getElementById('initialPaymentBox').style.display = mode === 'edit' ? 'none' : 'block';
    }
    window.openPurchaseModal = function () {
        setMode('create'); purchaseForm.reset(); 
        document.getElementById('supplier_id_hidden').value = '';
        document.getElementById('supplier_search_input').value = '';
        purchaseForm.querySelector('[name="tgl_pembelian"]').value = today; purchaseForm.querySelector('[name="initial_payment_date"]').value = today; purchaseForm.querySelector('[name="initial_payment_method"]').value = 'Tunai'; document.getElementById('initial_payment').value = '0'; tableBody.innerHTML = ''; createRow(); purchaseModal.classList.remove('hidden'); purchaseModal.style.display = 'flex';
    };
    window.closePurchaseModal = function () { purchaseModal.style.display = 'none'; purchaseModal.classList.add('hidden'); hideConfirmPurchaseModal(); };
    function showConfirmPurchaseModal() { confirmPurchaseModal.classList.remove('hidden'); confirmPurchaseModal.classList.add('flex'); }
    window.hideConfirmPurchaseModal = function () { confirmPurchaseModal.classList.add('hidden'); confirmPurchaseModal.classList.remove('flex'); };

    purchaseForm.addEventListener('submit', function(event) {
        if (purchaseAllowSubmit) return;
        event.preventDefault();
        if (!document.getElementById('supplier_id_hidden').value) {
            alert('Silakan pilih supplier dari daftar pencarian.');
            return;
        }
        let valid = true;
        tableBody.querySelectorAll('tr').forEach(row => { if (row.querySelector('.row-sku-hidden').value === '') valid = false; });
        if (!valid) { alert('Ada produk di tabel yang belum dipilih dengan benar dari daftar saran.'); return; }
        const totals = updateSummary();
        const initialPayment = formMode === 'create' ? parseNumber(document.getElementById('initial_payment').value) : 0;
        if (initialPayment > totals.grandTotal) { alert('Pembayaran awal tidak boleh melebihi total pembelian.'); return; }
        showConfirmPurchaseModal();
    });
    document.getElementById('executePurchaseSubmitBtn').addEventListener('click', function() { purchaseAllowSubmit = true; hideConfirmPurchaseModal(); purchaseForm.submit(); });

    window.openPurchaseEdit = async function (id) {
        try {
            const response = await fetch(`${purchaseDetailBaseUrl}/${id}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const payload = await response.json();
            if (!response.ok || !payload.success) throw new Error(payload.message || 'Gagal memuat data pembelian.');
            const data = payload.data; setMode('edit', data); 
            purchaseForm.querySelector('[name="tgl_pembelian"]').value = data.tgl_pembelian_value || today; 
            
            const foundSupplier = purchaseSuppliers.find(s => String(s.id) === String(data.supplier_id));
            document.getElementById('supplier_id_hidden').value = String(data.supplier_id || '');
            document.getElementById('supplier_search_input').value = foundSupplier ? foundSupplier.nama_supplier : (data.nama_supplier || '');
            
            document.getElementById('purchase_note').value = data.note || '';
            tableBody.innerHTML = '';
            (Array.isArray(data.details) && data.details.length ? data.details : []).forEach(item => createRow({name: item.nama_barang || item.kode_barang, sku: item.kode_barang, unit: item.satuan || 'Unit', qty: item.qty || 1, price: item.harga_beli || 0}));
            if (!tableBody.rows.length) createRow(); updateSummary(); purchaseModal.classList.remove('hidden'); purchaseModal.style.display = 'flex';
        } catch (error) { alert(error.message || 'Gagal membuka edit pembelian.'); }
    };

    function paymentBadgeHtml(status) {
        const cls = status === 'Lunas' ? 'bg-emerald-100 text-emerald-800' : (status === 'Dibayar Sebagian' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-700');
        return `<span class="px-2.5 py-0.5 rounded-md ${cls} text-[10px] font-bold uppercase tracking-wider">${escapeHtml(status || 'Belum Dibayar')}</span>`;
    }
    function setPurchaseDetailLoading() {
        document.getElementById('purchaseDetailSubtitle').textContent = 'Memuat data pembelian...';
        ['purchaseDetailInvoice','purchaseDetailDate','purchaseDetailSupplier','purchaseDetailPic','purchaseDetailAddress'].forEach(id => document.getElementById(id).textContent = '-');
        document.getElementById('purchaseDetailStatus').innerHTML = '-';
        document.getElementById('purchaseDetailGrandTotal').textContent = 'Rp 0'; document.getElementById('purchaseDetailPaidTotal').textContent = 'Rp 0'; document.getElementById('purchaseDetailRemaining').textContent = 'Rp 0';
        document.getElementById('purchaseDetailRows').innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-on-surface-variant">Memuat data...</td></tr>';
        document.getElementById('purchasePaymentRows').innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-on-surface-variant">Memuat data...</td></tr>';
    }
    window.openPurchaseDetail = async function (id) {
        const modal = document.getElementById('purchaseDetailModal'); setPurchaseDetailLoading(); modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.style.overflow = 'hidden';
        try {
            const response = await fetch(`${purchaseDetailBaseUrl}/${id}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const payload = await response.json(); if (!response.ok || !payload.success) throw new Error(payload.message || 'Gagal memuat dokumen.');
            const data = payload.data, supplier = data.supplier || {}, details = Array.isArray(data.details) ? data.details : [], payments = Array.isArray(data.payments) ? data.payments : [];
            document.getElementById('purchaseDetailSubtitle').textContent = 'Invoice Pembelian — Sistem Cloud POS'; document.getElementById('purchaseDetailInvoice').textContent = data.no_invoice || '-'; document.getElementById('purchaseDetailDate').textContent = data.tgl_pembelian || '-'; document.getElementById('purchaseDetailSupplier').textContent = supplier.nama_supplier || '-'; document.getElementById('purchaseDetailPic').textContent = `${supplier.nama_pic || '-'} (${supplier.no_hp || '-'})`; document.getElementById('purchaseDetailAddress').textContent = supplier.alamat || '-'; document.getElementById('purchaseDetailStatus').innerHTML = paymentBadgeHtml(data.payment_status || 'Belum Dibayar'); document.getElementById('purchaseDetailGrandTotal').textContent = formatRupiah(data.total_harga || 0); document.getElementById('purchaseDetailPaidTotal').textContent = formatRupiah(data.paid_total || 0); document.getElementById('purchaseDetailRemaining').textContent = formatRupiah(data.remaining_total || 0); document.getElementById('purchaseDetailInvoiceLink').href = data.invoice_url || '#';
            if (data.note) { document.getElementById('purchaseDetailNoteBox').classList.remove('hidden'); document.getElementById('purchaseDetailNote').textContent = data.note; } else { document.getElementById('purchaseDetailNoteBox').classList.add('hidden'); }
            document.getElementById('purchaseDetailRows').innerHTML = details.length ? details.map((item, index) => `<tr><td class="text-center py-3 px-3 text-on-surface-variant">${index + 1}</td><td class="py-3 px-3"><span class="font-bold text-on-surface block">${escapeHtml(item.nama_barang || '-')}</span><span class="font-mono text-[10px] text-on-surface-variant">${escapeHtml(item.kode_barang || '-')}</span></td><td class="text-center py-3 px-3 font-semibold">${Number(item.qty || 0).toLocaleString('id-ID', { maximumFractionDigits: 3 })}</td><td class="text-center py-3 px-3 text-on-surface-variant">${escapeHtml(item.satuan || 'Unit')}</td><td class="text-right py-3 px-3">${formatRupiah(item.harga_beli || 0)}</td><td class="text-right py-3 px-3 font-bold">${formatRupiah(item.subtotal || 0)}</td></tr>`).join('') : '<tr><td colspan="6" class="px-4 py-8 text-center text-on-surface-variant">Tidak ada barang tercatat.</td></tr>';
            document.getElementById('purchasePaymentRows').innerHTML = payments.length ? payments.map((p, index) => `<tr><td class="text-center py-3 px-3 text-on-surface-variant">${index + 1}</td><td class="py-3 px-3 font-semibold">${escapeHtml(p.payment_date || '-')}</td><td class="py-3 px-3">${escapeHtml(p.payment_method || '-')}</td><td class="py-3 px-3 text-on-surface-variant">${escapeHtml(p.payment_note || '-')}</td><td class="text-right py-3 px-3 font-bold text-primary-emerald">${formatRupiah(p.amount || 0)}</td></tr>`).join('') : '<tr><td colspan="5" class="px-4 py-8 text-center text-on-surface-variant">Belum ada pembayaran.</td></tr>';
        } catch (error) { document.getElementById('purchaseDetailSubtitle').textContent = 'Gagal memuat dokumen.'; document.getElementById('purchaseDetailRows').innerHTML = `<tr><td colspan="6" class="px-4 py-8 text-center font-bold text-error">${escapeHtml(error.message)}</td></tr>`; }
    };
    window.closePurchaseDetail = function () { const modal = document.getElementById('purchaseDetailModal'); modal.classList.add('hidden'); modal.classList.remove('flex'); document.body.style.overflow = ''; };

    window.openPaymentModal = function (id, invoice, remaining) {
        const modal = document.getElementById('paymentModal');
        const form = document.getElementById('paymentForm');
        const amount = document.getElementById('paymentAmount');
        const note = document.getElementById('paymentNote');
        const method = document.getElementById('paymentMethod');
        const date = document.getElementById('paymentDate');

        if (!modal || !form || !amount) {
            alert('Modal pembayaran tidak ditemukan. Refresh halaman lalu coba lagi.');
            return;
        }

        const safeRemaining = Number(remaining || 0);
        form.action = `${purchaseDetailBaseUrl}/${id}/payments`;
        document.getElementById('paymentModalSubtitle').textContent = `${invoice || '-'} • Sisa hutang ${formatRupiah(safeRemaining)}`;
        document.getElementById('paymentRemainingHint').textContent = `Sisa hutang: ${formatRupiah(safeRemaining)}`;

        amount.value = formatNumber(safeRemaining);
        if (note) note.value = '';
        if (method && !method.value) method.value = 'Tunai';
        if (date && !date.value) date.value = today;

        modal.classList.add('is-open');
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        setTimeout(() => {
            amount.focus();
            amount.select();
        }, 80);
    };

    window.closePaymentModal = function () {
        const modal = document.getElementById('paymentModal');
        if (!modal) return;
        modal.classList.remove('is-open', 'flex');
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    };

    window.addEventListener('click', function(event) {
        if (typeof purchaseModal !== 'undefined' && event.target === purchaseModal) {
            closePurchaseModal();
        }
    });

    const paymentModal = document.getElementById('paymentModal');
    if (paymentModal) {
        paymentModal.addEventListener('click', function (event) {
            const dialog = paymentModal.querySelector('[data-payment-dialog]');
            if (paymentModal.classList.contains('is-open') && dialog && !dialog.contains(event.target)) {
                closePaymentModal();
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            const modal = document.getElementById('paymentModal');
            if (modal && modal.classList.contains('is-open')) closePaymentModal();
        }
    });

</script>
@endpush
