@extends('layouts.app')

@section('title', 'Buat Data Tagihan Sales | Toko Bangunan 39')

@section('content')
<div class="p-lg space-y-lg flex-1 max-w-6xl mx-auto">

    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-md border-b border-outline-variant/60 pb-md">
        <div>
            <div class="flex items-center gap-2 text-xs text-on-surface-variant mb-1">
                <a href="{{ route('data-tagihan.index') }}" class="hover:text-primary transition-colors">Data Tagihan Sales</a>
                <span>/</span>
                <span class="text-on-surface font-bold">Buat Baru</span>
            </div>
            <h2 class="font-headline-xl text-headline-xl text-on-surface uppercase text-primary font-bold tracking-tight">Buat Lembar Data Tagihan</h2>
        </div>
        
        <div class="flex justify-end gap-2">
            <a href="{{ route('data-tagihan.index') }}" class="flex items-center gap-1 border border-outline-variant bg-white px-4 py-2 rounded-lg text-on-surface-variant hover:bg-surface-container-low text-xs font-bold transition-colors">
                <span>Batal</span>
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-xl bg-error/10 border border-error/30 text-error text-xs font-semibold flex items-center gap-2">
            <span class="material-symbols-outlined text-sm text-error">error</span>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <form action="{{ route('data-tagihan.store') }}" method="POST" id="formCreateTagihan" class="space-y-lg">
        @csrf

        <!-- Card Informasi Penugasan -->
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-md shadow-sm space-y-md">
            <h4 class="text-sm font-black text-on-surface tracking-tight border-b border-outline-variant pb-2">Informasi Penugasan Salesman</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
                <div>
                    <label class="block text-on-surface-variant font-bold text-[11px] uppercase tracking-wider mb-1.5">No. DO Tagihan</label>
                    <input type="text" value="{{ $nextNoDo }}" readonly class="h-10 w-full rounded-xl border border-outline-variant bg-surface-container-low px-3 text-xs font-mono font-bold text-primary outline-none cursor-not-allowed">
                    <p class="text-[10px] text-on-surface-variant/80 mt-1">Nomor otomatis ter-generate</p>
                </div>

                <div>
                    <label class="block text-on-surface-variant font-bold text-[11px] uppercase tracking-wider mb-1.5">Tanggal Penagihan <span class="text-error">*</span></label>
                    <input type="date" name="tgl_dt" value="{{ old('tgl_dt', $todayDate) }}" required class="h-10 w-full rounded-xl border border-outline-variant bg-white px-3 text-xs font-bold text-on-surface outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                </div>

                <div>
                    <label class="block text-on-surface-variant font-bold text-[11px] uppercase tracking-wider mb-1.5">Pilih Salesman <span class="text-error">*</span></label>
                    <select name="sales_id" id="salesSelect" required class="h-10 w-full rounded-xl border border-outline-variant bg-white px-3 text-xs font-bold text-on-surface outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                        <option value="">-- Pilih Salesman --</option>
                        @foreach($salesmen as $s)
                            <option value="{{ $s->id }}" {{ old('sales_id') == $s->id ? 'selected' : '' }}>{{ $s->nama_sales }} ({{ $s->kode_sales }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-on-surface-variant font-bold text-[11px] uppercase tracking-wider mb-1.5">Catatan Tambahan (Opsional)</label>
                <input type="text" name="catatan" value="{{ old('catatan') }}" placeholder="Catatan untuk sales..." class="h-10 w-full rounded-xl border border-outline-variant bg-white px-3 text-xs text-on-surface outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
            </div>
        </div>

        <!-- Panel Tabel Piutang Toko -->
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">
            <div class="px-4 py-3 border-b border-outline-variant flex flex-col sm:flex-row justify-between sm:items-center gap-2 bg-surface-container-low/40">
                <div>
                    <h4 class="text-sm font-black text-on-surface tracking-tight">Pilih Nota Piutang Yang Dibawa</h4>
                    <p class="text-[11px] text-on-surface-variant">Centang nota toko yang akan ditagih hari ini oleh salesman.</p>
                </div>

                <div class="relative min-w-0 sm:w-72">
                    <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-lg text-on-surface-variant">search</span>
                    <input type="text" id="invoiceSearchInput" placeholder="Cari invoice atau nama toko..." class="h-10 w-full rounded-full border border-outline-variant bg-white pl-9 pr-4 text-xs outline-none focus:border-primary focus:ring-2 focus:ring-primary/10" autocomplete="off">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant text-on-surface-variant font-bold">
                            <th class="px-4 py-3 w-12 text-center">
                                <input type="checkbox" id="checkAllInvoices" class="w-4 h-4 rounded text-primary focus:ring-0 cursor-pointer">
                            </th>
                            <th class="px-4 py-3">No. Invoice</th>
                            <th class="px-4 py-3">Tgl Transaksi</th>
                            <th class="px-4 py-3">Jatuh Tempo</th>
                            <th class="px-4 py-3">Nama Toko</th>
                            <th class="px-4 py-3">Sales Terkait</th>
                            <th class="px-4 py-3 text-right">Sisa Piutang</th>
                        </tr>
                    </thead>
                    <tbody id="invoiceTableBody" class="divide-y divide-outline-variant/60">
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-on-surface-variant font-medium">
                                Pilih Salesman di atas terlebih dahulu untuk memuat daftar piutang.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Summary Total -->
            <div class="p-md bg-surface-container-low/30 border-t border-outline-variant flex items-center justify-between">
                <div>
                    <span class="text-on-surface-variant font-bold text-[11px] uppercase tracking-wider block">Total Nota Dipilih</span>
                    <span id="selectedCountText" class="text-base font-black text-on-surface">0 Nota</span>
                </div>
                <div class="text-right">
                    <span class="text-on-surface-variant font-bold text-[11px] uppercase tracking-wider block">Total Nominal Tagihan</span>
                    <span id="selectedTotalText" class="text-xl font-black text-primary">Rp 0</span>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('data-tagihan.index') }}" class="flex items-center gap-1 border border-outline-variant bg-white px-4 py-2.5 rounded-lg text-on-surface-variant hover:bg-surface-container-low text-xs font-bold transition-colors">
                <span>Batal</span>
            </a>
            <button type="submit" id="btnSubmitForm" disabled class="flex items-center gap-1.5 bg-primary text-on-primary px-5 py-2.5 rounded-lg text-xs font-bold hover:brightness-110 shadow-sm transition-all opacity-50 cursor-not-allowed">
                <span class="material-symbols-outlined text-base">check</span>
                <span>Simpan & Buat Dokumen Tagihan</span>
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const salesSelect = document.getElementById('salesSelect');
    const invoiceSearchInput = document.getElementById('invoiceSearchInput');
    const tableBody = document.getElementById('invoiceTableBody');
    const checkAll = document.getElementById('checkAllInvoices');
    const selectedCountText = document.getElementById('selectedCountText');
    const selectedTotalText = document.getElementById('selectedTotalText');
    const btnSubmit = document.getElementById('btnSubmitForm');
    const form = document.getElementById('formCreateTagihan');

    let loadedInvoices = [];
    const selectedInvoices = new Map();
    const selectedInputsContainer = document.createElement('div');
    selectedInputsContainer.className = 'hidden';
    form.appendChild(selectedInputsContainer);

    function fetchInvoices() {
        const salesId = salesSelect.value;
        const search = invoiceSearchInput.value.trim();

        tableBody.innerHTML = `
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-on-surface-variant font-medium">
                    <span class="material-symbols-outlined animate-spin text-xl mb-1 text-primary block">sync</span>
                    Memuat data piutang...
                </td>
            </tr>
        `;

        let url = `{{ route('data-tagihan.unpaid_invoices') }}?sales_id=${salesId}&search=${encodeURIComponent(search)}`;

        fetch(url)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    loadedInvoices = res.data;
                    renderInvoices();
                } else {
                    tableBody.innerHTML = `<tr><td colspan="7" class="px-4 py-8 text-center text-error font-medium">Gagal memuat data.</td></tr>`;
                }
            })
            .catch(err => {
                console.error(err);
                tableBody.innerHTML = `<tr><td colspan="7" class="px-4 py-8 text-center text-error font-medium">Terjadi kesalahan koneksi.</td></tr>`;
            });
    }

    function renderInvoices() {
        if (loadedInvoices.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-on-surface-variant font-medium">
                        Tidak ada nota piutang tempo yang belum lunas.
                    </td>
                </tr>
            `;
            updateTotals();
            return;
        }

        let html = '';
        loadedInvoices.forEach(item => {
            const invoiceId = String(item.id);
            const isChecked = selectedInvoices.has(invoiceId) ? 'checked' : '';
            html += `
                <tr class="hover:bg-surface-container-low/30 transition-colors">
                    <td class="px-4 py-3.5 text-center">
                        <input type="checkbox" value="${item.id}" data-amount="${item.sisa_piutang}" ${isChecked} class="invoice-checkbox w-4 h-4 rounded text-primary focus:ring-0 cursor-pointer">
                    </td>
                    <td class="px-4 py-3.5 font-mono font-bold text-primary tracking-wide">${item.no_invoice}</td>
                    <td class="px-4 py-3.5 text-on-surface-variant">${item.tgl_transaksi_formatted}</td>
                    <td class="px-4 py-3.5 text-on-surface-variant">${item.jatuh_tempo_formatted}</td>
                    <td class="px-4 py-3.5 font-semibold text-on-surface">${item.nama_toko}</td>
                    <td class="px-4 py-3.5 text-on-surface-variant">${item.sales_name}</td>
                    <td class="px-4 py-3.5 text-right font-black text-on-surface">${item.sisa_piutang_formatted}</td>
                </tr>
            `;
        });

        tableBody.innerHTML = html;
        attachCheckboxListeners();
        updateTotals();
    }

    function attachCheckboxListeners() {
        const checkboxes = document.querySelectorAll('.invoice-checkbox');
        checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                const invoiceId = String(this.value);
                if (this.checked) {
                    selectedInvoices.set(invoiceId, parseInt(this.dataset.amount || '0', 10));
                } else {
                    selectedInvoices.delete(invoiceId);
                }
                updateTotals();
            });
        });
    }

    checkAll.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.invoice-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = this.checked;
            const invoiceId = String(cb.value);
            if (this.checked) {
                selectedInvoices.set(invoiceId, parseInt(cb.dataset.amount || '0', 10));
            } else {
                selectedInvoices.delete(invoiceId);
            }
        });
        updateTotals();
    });

    function updateTotals() {
        const visibleCheckboxes = Array.from(document.querySelectorAll('.invoice-checkbox'));
        const count = selectedInvoices.size;
        let total = 0;

        selectedInvoices.forEach(amount => {
            total += amount;
        });

        selectedInputsContainer.replaceChildren();
        selectedInvoices.forEach((amount, invoiceId) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'invoices[]';
            input.value = invoiceId;
            selectedInputsContainer.appendChild(input);
        });

        const checkedVisibleCount = visibleCheckboxes.filter(cb => cb.checked).length;
        checkAll.checked = visibleCheckboxes.length > 0 && checkedVisibleCount === visibleCheckboxes.length;
        checkAll.indeterminate = checkedVisibleCount > 0 && checkedVisibleCount < visibleCheckboxes.length;

        selectedCountText.textContent = `${count} Nota`;
        selectedTotalText.textContent = `Rp ${total.toLocaleString('id-ID')}`;

        if (count > 0) {
            btnSubmit.disabled = false;
            btnSubmit.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            btnSubmit.disabled = true;
            btnSubmit.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    salesSelect.addEventListener('change', function() {
        selectedInvoices.clear();
        updateTotals();
        fetchInvoices();
    });

    let searchTimeout;
    invoiceSearchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(fetchInvoices, 300);
    });

    if (salesSelect.value) {
        fetchInvoices();
    }
});
</script>
@endpush
@endsection
