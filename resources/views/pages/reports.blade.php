@extends('layouts.app')

@section('title', 'Laporan Profitabilitas & Margin | Toko Bangunan 39')

@section('content')
@php
    $activeQuery = request()->query();
    $selectedKategori = request('kategori_pelanggan', $kategoriPelanggan ?? 'all');
    $selectedMetode = request('metode_bayar', $metodeBayar ?? 'all');
    $sumOmset = 0;
    $sumModal = 0;
    $sumLaba = 0;
@endphp

<div class="p-lg space-y-lg flex-1">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-md shrink-0">
        <div>
            <!-- <nav class="flex text-[10px] font-bold text-on-surface-variant mb-1 gap-1.5 uppercase tracking-wider opacity-70">
                <span>Laporan</span>
                <span>/</span>
                <span class="text-primary">Profitabilitas</span>
            </nav> -->
            <!-- <h2 class="text-lg font-black text-primary uppercase tracking-wider">Laporan Profitabilitas & Margin</h2> -->
        
                <h1 class="font-headline-xl text-headline-xl text-on-surface uppercase text-primary">Laporan Profitabilitas & Margin</h1>
            <p class="text-xs text-on-surface-variant mt-0.5">Analisis historis read-only dari penjualan, modal, laba kotor, dan margin.</p>
        </div>
        <div class="flex items-center gap-2 self-start md:self-auto">
            <!-- <a href="{{ route('reports.export_pdf', $activeQuery) }}" class="flex items-center gap-1.5 border border-outline-variant bg-surface-container-lowest px-3 py-1.5 rounded-lg text-on-surface-variant hover:bg-surface-container-low text-xs font-bold transition-colors">
                <span class="material-symbols-outlined text-md">download</span>
                <span>Ekspor PDF</span>
            </a> -->
            <!-- <a href="{{ route('reports.export_excel', $activeQuery) }}" class="flex items-center gap-1.5 bg-primary text-on-primary px-3 py-1.5 rounded-lg text-xs font-bold hover:brightness-110 shadow-sm transition-all"> -->
            <a href="{{ route('reports.export_excel', $activeQuery) }}" class="flex items-center gap-1.5 bg-primary text-on-primary px-3 py-1.5 rounded-lg text-xs font-bold hover:brightness-110 shadow-sm transition-all">
                <span class="material-symbols-outlined text-md">file_export</span>
                <span>Ekspor Excel</span>
            </a>
        </div>
    </div>

    @if (! ($databaseReady ?? true))
        <div class="bg-error/10 border border-error/20 text-error rounded-xl px-4 py-2.5 flex items-start gap-2 text-sm">
            <span class="material-symbols-outlined text-lg mt-0.5">warning</span>
            <div>
                <p class="font-bold">Tabel transaksi belum lengkap.</p>
                <p class="text-xs opacity-90 mt-0.5">Pastikan modul penjualan, detail penjualan, dan master barang sudah dimigrasikan dengan benar.</p>
            </div>
        </div>
    @endif

    <form
    method="GET"
    action="{{ route('reports.index') }}"
    class="grid w-full grid-cols-1 gap-sm md:grid-cols-2 xl:grid-cols-[170px_170px_minmax(180px,1fr)_minmax(180px,1fr)_auto] xl:items-end"
>
    {{-- Tanggal mulai --}}
    <div class="min-w-0">
        <label
            for="start_date"
            class="mb-1 block text-label-sm font-bold uppercase tracking-wide text-on-surface-variant"
        >
            Tanggal Mulai
        </label>

        <input
            id="start_date"
            name="start_date"
            value="{{ request('start_date', $startDate ?? $defaultStartDate) }}"
            type="date"
            class="block h-11 w-full rounded-xl border border-outline-variant bg-surface px-3 text-body-md text-on-surface outline-none transition-all focus:border-primary focus:ring-4 focus:ring-primary/10"
        >
    </div>

    {{-- Tanggal selesai --}}
    <div class="min-w-0">
        <label
            for="end_date"
            class="mb-1 block text-label-sm font-bold uppercase tracking-wide text-on-surface-variant"
        >
            Tanggal Selesai
        </label>

        <input
            id="end_date"
            name="end_date"
            value="{{ request('end_date', $endDate ?? $defaultEndDate) }}"
            type="date"
            class="block h-11 w-full rounded-xl border border-outline-variant bg-surface px-3 text-body-md text-on-surface outline-none transition-all focus:border-primary focus:ring-4 focus:ring-primary/10"
        >
    </div>

    {{-- Kategori pelanggan --}}
    <div class="min-w-0">
        <label
            for="kategori_pelanggan"
            class="mb-1 block text-label-sm font-bold uppercase tracking-wide text-on-surface-variant"
        >
            Kategori Pelanggan
        </label>

        <select
            id="kategori_pelanggan"
            name="kategori_pelanggan"
            class="block h-11 w-full rounded-xl border border-outline-variant bg-surface px-3 text-body-md text-on-surface outline-none transition-all focus:border-primary focus:ring-4 focus:ring-primary/10"
        >
            <option value="all" @selected($selectedKategori === 'all')>
                Semua Kategori
            </option>

            <option value="user" @selected(strtolower($selectedKategori) === 'user')>
                User
            </option>

            <option value="toko" @selected(strtolower($selectedKategori) === 'toko')>
                Toko
            </option>

            <option value="sales" @selected(strtolower($selectedKategori) === 'sales')>
                Sales
            </option>
        </select>
    </div>

    {{-- Metode pembayaran --}}
    <div class="min-w-0">
        <label
            for="metode_bayar"
            class="mb-1 block text-label-sm font-bold uppercase tracking-wide text-on-surface-variant"
        >
            Metode Bayar
        </label>

        <select
            id="metode_bayar"
            name="metode_bayar"
            class="block h-11 w-full rounded-xl border border-outline-variant bg-surface px-3 text-body-md text-on-surface outline-none transition-all focus:border-primary focus:ring-4 focus:ring-primary/10"
        >
            <option value="all" @selected($selectedMetode === 'all')>
                Semua Metode
            </option>

            <option value="cash" @selected(strtolower($selectedMetode) === 'cash')>
                Cash
            </option>

            <option value="tempo" @selected(strtolower($selectedMetode) === 'tempo')>
                Tempo
            </option>
        </select>
    </div>

    {{-- Tombol filter --}}
    <button
        type="submit"
        class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-primary px-lg font-bold text-on-primary transition-opacity hover:opacity-90"
    >
        <span class="material-symbols-outlined text-[19px]">
            filter_alt
        </span>

        <span>Terapkan</span>
    </button>
</form>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-md">
        <div class="bg-surface-container-lowest border border-outline-variant p-md rounded-xl shadow-sm flex items-center justify-between gap-3">
            <div class="space-y-1">
                <p class="text-on-surface-variant font-bold text-[11px] uppercase tracking-wider">Total Omset</p>
                <h3 class="text-xl font-black text-primary">Rp {{ number_format((float) ($totalOmset ?? 0), 0, ',', '.') }}</h3>
            </div>
            <div class="w-10 h-10 bg-primary/10 text-primary rounded-xl flex items-center justify-center shrink-0 border border-primary/20">
                <span class="material-symbols-outlined text-xl">payments</span>
            </div>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant p-md rounded-xl shadow-sm flex items-center justify-between gap-3">
            <div class="space-y-1">
                <p class="text-on-surface-variant font-bold text-[11px] uppercase tracking-wider">Total Laba Kotor</p>
                <h3 class="text-xl font-black text-emerald-600">Rp {{ number_format((float) ($totalLabaKotor ?? 0), 0, ',', '.') }}</h3>
            </div>
            <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center shrink-0 border border-emerald-200">
                <span class="material-symbols-outlined text-xl">account_balance_wallet</span>
            </div>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant p-md rounded-xl shadow-sm flex items-center justify-between gap-3">
            <div class="space-y-1">
                <p class="text-on-surface-variant font-bold text-[11px] uppercase tracking-wider">Average Margin %</p>
                <h3 class="text-xl font-black text-on-surface">{{ number_format((float) ($averageMarginPercent ?? 0), 2, ',', '.') }}%</h3>
            </div>
            <div class="w-10 h-10 bg-surface-container-high text-on-surface-variant rounded-xl flex items-center justify-center shrink-0 border border-outline-variant">
                <span class="material-symbols-outlined text-xl">percent</span>
            </div>
        </div>
    </div>

    <!-- <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
        <div class="bg-surface-container-lowest border border-outline-variant p-sm rounded-xl shadow-sm flex items-center gap-3">
            <div class="w-8 h-8 bg-emerald-50 text-emerald-700 rounded-lg flex items-center justify-center shrink-0 border border-emerald-100">
                <span class="material-symbols-outlined text-md">check_circle</span>
            </div>
            <div>
                <p class="text-on-surface-variant font-bold text-[10px] uppercase tracking-wider">Total Lunas (CASH)</p>
                <h4 class="text-md font-bold text-emerald-700">Rp {{ number_format((float) ($totalLunas ?? 0), 0, ',', '.') }}</h4>
            </div>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant p-sm rounded-xl shadow-sm flex items-center gap-3">
            <div class="w-8 h-8 bg-amber-50 text-amber-700 rounded-lg flex items-center justify-center shrink-0 border border-amber-100">
                <span class="material-symbols-outlined text-md">pending_actions</span>
            </div>
            <div>
                <p class="text-on-surface-variant font-bold text-[10px] uppercase tracking-wider">Total Hutang (TEMPO)</p>
                <h4 class="text-md font-bold text-amber-700">Rp {{ number_format((float) ($totalHutang ?? 0), 0, ',', '.') }}</h4>
            </div>
        </div>
    </div> -->

    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">
        <div class="px-4 py-3 border-b border-outline-variant flex flex-col sm:flex-row justify-between sm:items-center gap-2 bg-surface-container-low/40">
            <div>
                <h4 class="text-sm font-black text-on-surface tracking-tight">Detail Profitabilitas Nota</h4>
                <p class="text-[11px] text-on-surface-variant mt-0.5">Semua angka diambil dari histori transaksi secara otomatis (Audit-Mode).</p>
            </div>
            <div class="self-start sm:self-auto">
                <!-- <span class="inline-flex items-center gap-1 rounded-md bg-primary/10 text-primary px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider">
                    <span class="material-symbols-outlined text-xs">lock</span>
                    Read-only Audit Mode
                </span> -->
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs" id="reportTable">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant text-on-surface-variant font-bold">
                        <th class="px-4 py-3 text-center w-12">No</th>
                        <th class="px-4 py-3">No. Invoice</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Pelanggan</th>
                        <th class="px-4 py-3 text-center">Segmen</th>
                        <th class="px-4 py-3 text-center">Metode & Status</th>
                        <th class="px-4 py-3 text-right">Total Penjualan</th>
                        <th class="px-4 py-3 text-right">Total Modal Jual</th>
                        <th class="px-4 py-3 text-right">Laba Keuntungan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60">
                    @forelse($transactions as $key => $item)
                        @php
                            $rowOmset = (float) ($item->total_penjualan ?? 0);
                            $rowModal = (float) ($item->total_modal ?? 0);
                            $rowLaba = (float) ($item->laba_kotor ?? 0);
                            $rowMargin = $rowOmset > 0 ? ($rowLaba / $rowOmset) * 100 : 0;
                            $sumOmset += $rowOmset;
                            $sumModal += $rowModal;
                            $sumLaba += $rowLaba;
                        @endphp
                        <tr class="hover:bg-surface-container-low/30 transition-colors">
                            <td class="px-4 py-3.5 text-center text-on-surface-variant font-medium">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3.5 font-bold text-primary font-mono tracking-wide">{{ $item->no_invoice }}</td>
                            <td class="px-4 py-3.5 text-on-surface-variant font-medium">{{ \Carbon\Carbon::parse($item->tgl_transaksi)->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3.5 font-semibold text-on-surface">{{ $item->nama_pelanggan }}</td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="inline-flex rounded-md bg-surface-container-high px-2 py-0.5 text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">{{ $item->tipe_pelanggan }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="inline-flex rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $item->metode_bayar === 'CASH' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $item->metode_bayar }}
                                </span>

                                @if($item->metode_bayar === 'CASH' || (int)($item->sisa_piutang ?? 0) === 0)
                                    <span class="block text-[9px] font-extrabold mt-1 uppercase tracking-tight text-emerald-600">
                                        ● LUNAS
                                    </span>
                                @else
                                    <span class="block text-[9px] font-extrabold mt-1 uppercase tracking-tight text-error">
                                        ● UTANG
                                    </span>
                                    <span class="block text-[8px] font-mono font-bold text-on-surface-variant opacity-80 mt-0.5">
                                        Bal: Rp {{ number_format((float)$item->sisa_piutang, 0, ',', '.') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-on-surface">Rp {{ number_format($rowOmset, 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right text-on-surface-variant font-medium">Rp {{ number_format($rowModal, 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right">
                                <span class="font-black {{ $rowLaba >= 0 ? 'text-emerald-600' : 'text-error' }}">
                                    Rp {{ number_format($rowLaba, 0, ',', '.') }}
                                </span>
                                <span class="block text-[10px] font-bold text-on-surface-variant font-mono mt-0.5">{{ number_format($rowMargin, 2, ',', '.') }}% Margin</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-on-surface-variant">
                                <div class="flex flex-col items-center gap-1.5 opacity-60">
                                    <span class="material-symbols-outlined text-3xl">analytics</span>
                                    <p class="text-xs font-medium">Tidak ada data transaksi yang sesuai dengan filter laporan.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-surface-container-low/50 font-bold border-t border-outline-variant shadow-[inset_0_1px_0_rgba(0,0,0,0.05)]">
                    <tr class="text-on-surface">
                        <td colspan="6" class="px-4 py-3 text-right text-[11px] uppercase tracking-wider font-bold text-on-surface-variant">Total Halaman Ini</td>
                        <td class="px-4 py-3 text-right text-primary font-bold">Rp {{ number_format($sumOmset, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-on-surface-variant font-medium">Rp {{ number_format($sumModal, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-emerald-600 font-black">Rp {{ number_format($sumLaba, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let activeReceivableData = null;
    const managementModal = document.getElementById('management-modal');
    const paymentForm = document.getElementById('receivable-payment-form');
    const confirmPaymentModal = document.getElementById('confirm-payment-modal');
    const executePaymentSubmit = document.getElementById('execute-payment-submit');

    function rupiah(value) {
        const numericValue = Number(value || 0);
        return 'Rp ' + numericValue.toLocaleString('id-ID');
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function openReceivableModal(id) {
        if (!managementModal) return;
        managementModal.classList.remove('hidden');
        managementModal.classList.add('flex');
        document.getElementById('modal-invoice-title').textContent = 'Memuat data nota...';
        document.getElementById('modal-remaining-balance').textContent = 'Rp 0';
        document.getElementById('modal-due-date').textContent = '-';
        document.getElementById('payment-history-body').innerHTML = '<tr><td class="px-3 py-8 text-center text-on-surface-variant opacity-60" colspan="4">Memuat riwayat pembayaran...</td></tr>';
        document.getElementById('payment-penjualan-id').value = id;
        document.getElementById('new-payment-amount').value = '';

        fetch('/receivables/' + id, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Gagal mengambil detail piutang.');
                }
                return response.json();
            })
            .then(data => {
                activeReceivableData = data;
                document.getElementById('modal-invoice-title').textContent = data.no_invoice + ' - ' + data.nama_pelanggan;
                document.getElementById('modal-remaining-balance').textContent = data.sisa_piutang_label;
                document.getElementById('modal-due-date').textContent = data.jatuh_tempo_label || '-';
                document.getElementById('payment-penjualan-id').value = data.id;
                
                const balanceCard = document.getElementById('modal-balance-card');
                if (balanceCard) {
                    balanceCard.classList.remove('bg-error/90', 'bg-emerald-600');
                    balanceCard.classList.add(Number(data.sisa_piutang) <= 0 ? 'bg-emerald-600' : 'bg-error/90');
                }

                const body = document.getElementById('payment-history-body');
                if (!data.payments || data.payments.length === 0) {
                    body.innerHTML = '<tr><td class="px-3 py-8 text-center text-on-surface-variant opacity-60" colspan="4">Belum ada cicilan masuk.</td></tr>';
                    return;
                }

                body.innerHTML = data.payments.map((payment, index) => `
                    <tr class="hover:bg-surface-container-low/40 transition-colors">
                        <td class="px-3 py-2 w-12 text-center text-on-surface-variant font-medium">${index + 1}</td>
                        <td class="px-3 py-2 text-on-surface font-medium">${escapeHtml(payment.tgl_bayar_label)}</td>
                        <td class="px-3 py-2 text-right font-bold text-emerald-600">${escapeHtml(payment.nominal_label)}</td>
                        <td class="px-3 py-2 pl-4 text-on-surface-variant">${escapeHtml(payment.penerima_kasir)}</td>
                    </tr>
                `).join('');
            })
            .catch(error => {
                document.getElementById('payment-history-body').innerHTML = '<tr><td class="px-3 py-8 text-center text-error font-medium" colspan="4">' + escapeHtml(error.message) + '</td></tr>';
            });
    }

    function closeReceivableModal() {
        if (!managementModal) return;
        managementModal.classList.add('hidden');
        managementModal.classList.remove('flex');
    }

    function showPaymentConfirmModal(message) {
        if (!confirmPaymentModal) return;
        document.getElementById('confirm-payment-text').textContent = message;
        confirmPaymentModal.classList.remove('hidden');
        confirmPaymentModal.classList.add('flex');
    }

    function hidePaymentConfirmModal() {
        if (!confirmPaymentModal) return;
        confirmPaymentModal.classList.add('hidden');
        confirmPaymentModal.classList.remove('flex');
    }

    if (paymentForm) {
        paymentForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const amount = Number(document.getElementById('new-payment-amount').value || 0);
            const remaining = Number(activeReceivableData ? activeReceivableData.sisa_piutang : 0);

            if (!activeReceivableData) {
                alert('Data nota belum selesai dimuat.');
                return;
            }

            if (amount <= 0) {
                alert('Silakan masukkan nominal pembayaran yang valid.');
                return;
            }

            if (amount > remaining) {
                alert('Nominal pembayaran melebihi sisa piutang.');
                return;
            }

            showPaymentConfirmModal('Apakah Anda yakin nominal setoran cicilan sebesar ' + rupiah(amount) + ' sudah dihitung benar secara fisik di laci kassa?');
        });
    }

    if (executePaymentSubmit) {
        executePaymentSubmit.addEventListener('click', function() {
            hidePaymentConfirmModal();
            paymentForm.submit();
        });
    }
</script>
@endpush