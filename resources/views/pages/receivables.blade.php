@extends('layouts.app')

@section('title', 'Receivables Ledger | Toko Bangunan 39')

@section('content')
<!-- Wrapper Halaman Internal -->
<div class="p-lg space-y-lg flex-1">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-md border-b border-outline-variant/60 pb-md">
        <div>
            {{-- Berubah menjadi Piutang Penjualan & Warna diubah ke text-primary --}}
            <h2 class="font-headline-xl text-headline-xl text-on-surface uppercase text-primary font-bold tracking-tight">Piutang Pelanggan / Penjualan</h2>
            {{-- Deskripsi disesuaikan untuk memantau tagihan pelanggan --}}
            <p class="text-xs text-on-surface-variant mt-0.5">Pantau tagihan penjualan, sisa piutang pelanggan, status pembayaran tempo, dan riwayat pelunasan invoice.</p>
        </div>
        
        <div id="receivables-export-actions-patched" class="flex justify-end gap-2">
            <a class="flex items-center gap-1.5 bg-primary text-on-primary px-4 py-2 rounded-lg text-xs font-bold hover:brightness-110 shadow-sm transition-all" href="{{ route('receivables.export_excel', request()->query()) }}">
                <span class="material-symbols-outlined text-md">file_export</span>
                <span>Export Excel</span>
            </a>
            <a class="flex items-center gap-1.5 border border-outline-variant bg-surface-container-lowest px-4 py-2 rounded-lg text-on-surface-variant hover:bg-surface-container-low text-xs font-bold transition-colors" href="{{ route('receivables.export_pdf', request()->query()) }}">
                <span class="material-symbols-outlined text-md">picture_as_pdf</span>
                <span>Export PDF</span>
            </a>
        </div>
    </div>
    <!-- Grid Kartu Summary -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-md">
        <div class="bg-surface-container-lowest border border-outline-variant p-md rounded-xl shadow-sm space-y-1">
            <p class="text-on-surface-variant font-bold text-[11px] uppercase tracking-wider">Total Outstanding</p>
            <h3 class="text-xl font-black text-primary">Rp {{ number_format($totalOutstanding ?? 0, 0, ',', '.') }}
            </h3>
            <div class="flex items-center gap-1 text-emerald-600 pt-1 text-xs font-medium">
                <span class="material-symbols-outlined text-sm">trending_up</span>
                <span>Saldo piutang aktif</span>
            </div>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant p-md rounded-xl shadow-sm space-y-1">
            <p class="text-on-surface-variant font-bold text-[11px] uppercase tracking-wider">Active Debtors</p>
            <h3 class="text-xl font-black text-on-surface">{{ number_format($activeDebtors ?? 0, 0, ',', '.') }}
            </h3>
            <p class="text-on-surface-variant text-xs pt-1">Pelanggan tempo aktif</p>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant p-md rounded-xl shadow-sm space-y-1">
            <p class="text-on-surface-variant font-bold text-[11px] uppercase tracking-wider">Overdue (30d+)</p>
            <h3 class="text-xl font-black text-error">Rp {{ number_format($overdueTotal ?? 0, 0, ',', '.') }}</h3>
            <p class="text-error/80 text-xs pt-1 font-medium">Butuh tindak lanjut</p>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant p-md rounded-xl shadow-sm space-y-1">
            <p class="text-on-surface-variant font-bold text-[11px] uppercase tracking-wider">Recovery Rate</p>
            <h3 class="text-xl font-black text-emerald-600">{{ number_format($recoveryRate ?? 0, 1, ',', '.') }}%
            </h3>
            <p class="text-on-surface-variant text-xs pt-1">Rasio cicilan masuk</p>
        </div>
    </div>


    <!-- Panel Tabel Ledger -->
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">
        <div
            class="px-4 py-3 border-b border-outline-variant flex flex-col sm:flex-row justify-between sm:items-center gap-2 bg-surface-container-low/40">
            <div>
                <h4 class="text-sm font-black text-on-surface tracking-tight">Receivables Oversight Ledger</h4>
                @if (($search ?? '') !== '')
                    <p class="mt-0.5 text-[11px] text-on-surface-variant">Hasil pencarian: <strong>{{ $search }}</strong></p>
                @endif
            </div>
            <form method="GET" action="{{ route('receivables.index') }}" class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                <select
                    name="status"
                    onchange="this.form.submit()"
                    class="h-10 rounded-xl border border-outline-variant bg-white px-3 text-xs font-bold text-on-surface outline-none focus:border-primary focus:ring-2 focus:ring-primary/10"
                >
                    <option value="belum_lunas" {{ ($statusFilter ?? 'belum_lunas') === 'belum_lunas' ? 'selected' : '' }}>Belum Lunas (Aktif)</option>
                    <option value="lunas" {{ ($statusFilter ?? '') === 'lunas' ? 'selected' : '' }}>Sudah Lunas</option>
                    <option value="semua" {{ ($statusFilter ?? '') === 'semua' ? 'selected' : '' }}>Semua Piutang</option>
                </select>
                <div class="relative min-w-0 flex-1 sm:w-72">
                    <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-lg text-on-surface-variant">search</span>
                    <input
                        type="text"
                        name="search"
                        value="{{ $search ?? '' }}"
                        placeholder="Cari invoice, customer, sales, kode, alamat..."
                        class="h-10 w-full rounded-full border border-outline-variant bg-white pl-9 pr-4 text-xs outline-none focus:border-primary focus:ring-2 focus:ring-primary/10"
                        autocomplete="off"
                    >
                </div>
                <button type="submit" class="flex h-10 items-center gap-1 rounded-xl bg-primary px-3 text-xs font-bold text-on-primary hover:brightness-110">
                    <span class="material-symbols-outlined text-base">search</span>Cari
                </button>
                @if (($search ?? '') !== '' || ($statusFilter ?? 'belum_lunas') !== 'belum_lunas')
                    <a href="{{ route('receivables.index') }}" class="flex h-10 items-center gap-1 rounded-xl border border-outline-variant bg-white px-3 text-xs font-bold text-on-surface-variant hover:bg-surface-container-low">
                        <span class="material-symbols-outlined text-base">close</span>Reset
                    </a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr
                        class="bg-surface-container-low border-b border-outline-variant text-on-surface-variant font-bold">
                        <th class="px-4 py-3">No. Invoice</th>
                        <th class="px-4 py-3">Customer Name</th>
                        <th class="px-4 py-3">Sales Name</th>
                        <th class="px-4 py-3">Due Date</th>
                        <th class="px-4 py-3 text-center">Remaining Days</th>
                        <th class="px-4 py-3 text-right">Initial Debt</th>
                        <th class="px-4 py-3 text-right">Total Paid</th>
                        <th class="px-4 py-3 text-right">Balance</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60">
                    @forelse($debts as $item)
                    @php
                    $totalPaid = max(0, (int) $item->total_belanja - (int) $item->sisa_piutang);
                    $today = \Illuminate\Support\Carbon::today();
                    $dueDate = $item->jatuh_tempo ? \Illuminate\Support\Carbon::parse($item->jatuh_tempo) : null;
                    $diffDays = $dueDate ? $today->diffInDays($dueDate, false) : null;
                    @endphp
                    <tr class="hover:bg-surface-container-low/30 transition-colors">
                        <td class="px-4 py-3.5 font-bold text-primary font-mono tracking-wide">
                            {{ $item->no_invoice }}</td>
                        <td class="px-4 py-3.5 font-semibold text-on-surface">{{ $item->nama_pelanggan }}</td>
                        <td class="px-4 py-3.5 text-on-surface-variant">
                            {{ optional($item->sales)->nama_sales ?? '-' }}</td>
                        <td class="px-4 py-3.5 text-on-surface-variant">
                            {{ $dueDate ? $dueDate->translatedFormat('d M Y') : '-' }}</td>
                        <td class="px-4 py-3.5 text-center">
                            @if (is_null($diffDays))
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-surface-container-high text-on-surface-variant uppercase tracking-wider">No
                                Due Date</span>
                            @elseif ($diffDays < 0) <span
                                class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-error/10 text-error uppercase tracking-wider">
                                {{ abs($diffDays) }} Days Overdue</span>
                                @elseif ($diffDays === 0)
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800 uppercase tracking-wider">Due
                                    Today</span>
                                @else
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-primary/10 text-primary uppercase tracking-wider">{{ $diffDays }}
                                    Days Left</span>
                                @endif
                        </td>
                        <td class="px-4 py-3.5 text-right text-on-surface-variant font-medium">Rp
                            {{ number_format((int) $item->total_belanja, 0, ',', '.') }}</td>
                        <td class="px-4 py-3.5 text-right text-emerald-600 font-medium">Rp
                            {{ number_format($totalPaid, 0, ',', '.') }}</td>
                        <td class="px-4 py-3.5 text-right font-bold text-error">Rp
                            {{ number_format((int) $item->sisa_piutang, 0, ',', '.') }}</td>
                        <td class="px-4 py-3.5 text-center">
                            <button
                                class="bg-primary text-on-primary font-bold text-xs px-3 py-1.5 rounded-lg hover:brightness-110 active:scale-95 transition-all shadow-sm"
                                type="button" onclick="openReceivableModal({{ $item->id }})">
                                Kelola Piutang
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td class="px-4 py-12 text-center text-on-surface-variant" colspan="9">
                            <div class="flex flex-col items-center gap-1.5 opacity-60">
                                <span class="material-symbols-outlined text-3xl">account_balance_wallet</span>
                                <p class="text-xs font-medium">{{ ($search ?? '') !== '' ? 'Data piutang tidak ditemukan untuk pencarian tersebut.' : 'Belum ada nota tempo/piutang yang tercatat.' }}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($debts->isNotEmpty())
                <tfoot class="border-t-2 border-outline-variant bg-surface-container-low/60 text-xs font-bold text-on-surface">
                    <tr>
                        <td colspan="5" class="px-4 py-3.5 text-left font-black uppercase tracking-wider text-on-surface-variant">Total (Sesuai Filter)</td>
                        <td class="px-4 py-3.5 text-right font-bold text-on-surface-variant font-mono">
                            Rp {{ number_format($totalFilteredInitial, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3.5 text-right font-bold text-emerald-600 font-mono">
                            Rp {{ number_format($totalFilteredPaid, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3.5 text-right font-extrabold text-error font-mono">
                            Rp {{ number_format($totalFilteredOutstanding, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3.5"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        @if(method_exists($debts, 'links'))
        <div class="px-4 py-2 border-t border-outline-variant bg-surface-container-low/20">
            {{ $debts->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@push('modals')
<!-- Modal Utama Detail Piutang -->
<div class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
    id="management-modal">
    <div
        class="w-full max-w-4xl bg-surface-container-lowest rounded-xl shadow-2xl overflow-hidden border border-outline-variant flex flex-col max-h-[90vh]">

        <!-- Modal Header -->
        <div
            class="px-4 py-3 border-b border-outline-variant flex justify-between items-center bg-surface-container-low/50">
            <div class="flex items-center gap-3">
                <div class="bg-primary/10 text-primary p-2 rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-lg">account_balance_wallet</span>
                </div>
                <div>
                    <h3 class="text-sm font-black text-on-surface">Kelola &amp; Detail Piutang</h3>
                    <p class="text-[11px] text-on-surface-variant font-medium mt-0.5" id="modal-invoice-title">Memuat
                        data nota...</p>
                </div>
            </div>
            <button
                class="w-8 h-8 flex items-center justify-center hover:bg-error/10 hover:text-error transition-colors rounded-full"
                type="button" onclick="closeReceivableModal()">
                <span class="material-symbols-outlined text-md">close</span>
            </button>
        </div>

        <!-- Modal Body Content (Scrollable) -->
        <div class="p-4 overflow-y-auto space-y-md flex-1 bg-surface-container-lowest">
            <!-- Card Balance Real-time -->
            <div class="bg-error/90 text-white p-4 rounded-xl shadow-sm flex flex-col sm:flex-row justify-between sm:items-center gap-3 transition-colors duration-200"
                id="modal-balance-card">
                <div>
                    <p class="text-[10px] uppercase font-bold tracking-wider opacity-80 mb-1">Total Sisa Piutang Saat
                        Ini</p>
                    <h2 class="text-2xl font-black font-mono tracking-tight" id="modal-remaining-balance">Rp 0</h2>
                </div>
                <div class="bg-white/10 px-4 py-2 rounded-lg border border-white/20 text-left sm:text-right shrink-0">
                    <p class="text-[10px] font-bold uppercase tracking-wider opacity-80">Batas Jatuh Tempo</p>
                    <p class="text-sm font-bold mt-0.5" id="modal-due-date">-</p>
                </div>
            </div>

            <!-- Grid Layout Form & Riwayat -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-md">
                <!-- Sisi Kiri: Riwayat Pembayaran -->
                <div class="space-y-2 flex flex-col">
                    <h4
                        class="text-xs font-bold text-on-surface uppercase tracking-wider flex items-center gap-1.5 shrink-0">
                        <span class="material-symbols-outlined text-primary text-md">history</span>
                        <span>Riwayat Pembayaran Cicilan</span>
                    </h4>
                    <div
                        class="border border-outline-variant/70 rounded-xl overflow-hidden bg-surface-container-low/20 flex-1 min-h-[200px]">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead
                                class="bg-surface-container-low border-b border-outline-variant text-on-surface-variant font-bold">
                                <tr>
                                    <th class="px-3 py-2 w-12 text-center">No</th>
                                    <th class="px-3 py-2">Tgl Bayar</th>
                                    <th class="px-3 py-2 text-right">Nominal</th>
                                    <th class="px-3 py-2 pl-4">Kasir</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/40" id="payment-history-body">
                                <tr>
                                    <td class="px-3 py-8 text-center text-on-surface-variant opacity-60" colspan="4">
                                        Pilih nota untuk melihat riwayat.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sisi Kanan: Form Input Pembayaran Baru -->
                <div class="space-y-2">
                    <h4 class="text-xs font-bold text-on-surface uppercase tracking-wider flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-primary text-md">add_circle</span>
                        <span>Input Cicilan Baru</span>
                    </h4>
                    <form
                        class="bg-surface-container-low border border-outline-variant p-4 rounded-xl space-y-md shadow-sm"
                        id="receivable-payment-form" method="POST" action="{{ route('receivables.store_payment') }}">
                        @csrf
                        <input type="hidden" name="penjualan_id" id="payment-penjualan-id" value="" />

                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider"
                                id="payment-amount-label" for="new-payment-amount">Nominal Uang Masuk</label>
                            <div class="relative">
                                <span
                                    class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-on-surface-variant text-base">Rp</span>
                                <input
                                    class="w-full pl-12 pr-4 py-2.5 rounded-xl border border-outline-variant focus:ring-2 focus:ring-primary/20 focus:border-primary font-black font-mono text-xl bg-white outline-none transition-all"
                                    id="new-payment-amount" name="nominal" placeholder="0" type="number" min="1"
                                    required />
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider"
                                for="payment-date">Tanggal Bayar</label>
                            <div class="relative">
                                <span
                                    class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-md">calendar_today</span>
                                <input
                                    class="w-full pl-9 pr-4 py-2 bg-white border border-outline-variant rounded-xl text-xs font-medium focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all"
                                    id="payment-date" name="tgl_bayar" type="date" value="{{ now()->toDateString() }}"
                                    required />
                            </div>
                        </div>

                        <button
                            class="w-full bg-primary text-on-primary py-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition-all hover:brightness-110 active:scale-[0.98] shadow-md mt-2"
                            type="submit">
                            <span class="material-symbols-outlined text-base">save</span>
                            <span>Simpan Cicilan</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-4 py-3 border-t border-outline-variant bg-surface-container-low/30 flex justify-end shrink-0">
            <button
                class="px-5 py-2 rounded-xl border border-outline-variant text-on-surface-variant hover:bg-surface-container-high text-xs font-bold transition-colors"
                type="button" onclick="closeReceivableModal()">
                Tutup Detail
            </button>
        </div>
    </div>
</div>

<!-- Modal Tingkat Dua: Konfirmasi Eksekusi Pembayaran -->
<div class="fixed inset-0 z-[70] hidden items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    id="confirm-payment-modal">
    <div
        class="w-full max-w-sm bg-surface-container-lowest rounded-xl border border-outline-variant shadow-2xl p-4 space-y-md text-xs">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-xl">payments</span>
            </div>
            <div>
                <h3 class="font-bold text-on-surface text-sm leading-tight">Konfirmasi Cicilan</h3>
                <p class="text-on-surface-variant mt-0.5">Validasi uang fisik sebelum ditulis ke ledger.</p>
            </div>
        </div>
        <p class="text-on-surface font-medium leading-relaxed" id="confirm-payment-text">Apakah Anda yakin nominal
            setoran cicilan sudah benar?</p>
        <div class="flex justify-end gap-2 pt-2 border-t border-outline-variant/60">
            <button
                class="px-3 py-1.5 rounded-lg border border-outline-variant font-bold text-on-surface-variant hover:bg-surface-container-low transition-colors"
                type="button" onclick="hidePaymentConfirmModal()">Periksa Lagi</button>
            <button class="px-3 py-1.5 rounded-lg bg-primary text-on-primary font-bold active:scale-95 transition-all"
                type="button" id="execute-payment-submit">Ya, Eksekusi</button>
        </div>
    </div>
    
</div>
@endpush

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
        managementModal.classList.remove('hidden');
        managementModal.classList.add('flex');
        document.getElementById('modal-invoice-title').textContent = 'Memuat data nota...';
        document.getElementById('modal-remaining-balance').textContent = 'Rp 0';
        document.getElementById('modal-due-date').textContent = '-';
        document.getElementById('payment-history-body').innerHTML =
            '<tr><td class="px-3 py-8 text-center text-on-surface-variant opacity-60" colspan="4">Memuat riwayat pembayaran...</td></tr>';
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
                document.getElementById('modal-invoice-title').textContent = data.no_invoice + ' - ' + data
                    .nama_pelanggan;
                document.getElementById('modal-remaining-balance').textContent = data.sisa_piutang_label;
                document.getElementById('modal-due-date').textContent = data.jatuh_tempo_label || '-';
                document.getElementById('payment-penjualan-id').value = data.id;

                const balanceCard = document.getElementById('modal-balance-card');
                balanceCard.classList.remove('bg-error/90', 'bg-emerald-600');
                balanceCard.classList.add(Number(data.sisa_piutang) <= 0 ? 'bg-emerald-600' : 'bg-error/90');

                const body = document.getElementById('payment-history-body');
                if (!data.payments || data.payments.length === 0) {
                    body.innerHTML =
                        '<tr><td class="px-3 py-8 text-center text-on-surface-variant opacity-60" colspan="4">Belum ada cicilan masuk.</td></tr>';
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
                document.getElementById('payment-history-body').innerHTML =
                    '<tr><td class="px-3 py-8 text-center text-error font-medium" colspan="4">' + escapeHtml(error
                        .message) + '</td></tr>';
            });
    }

    function closeReceivableModal() {
        managementModal.classList.add('hidden');
        managementModal.classList.remove('flex');
    }

    function showPaymentConfirmModal(message) {
        document.getElementById('confirm-payment-text').textContent = message;
        confirmPaymentModal.classList.remove('hidden');
        confirmPaymentModal.classList.add('flex');
    }

    function hidePaymentConfirmModal() {
        confirmPaymentModal.classList.add('hidden');
        confirmPaymentModal.classList.remove('flex');
    }

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

        showPaymentConfirmModal('Apakah Anda yakin nominal setoran cicilan sebesar ' + rupiah(amount) +
            ' sudah dihitung benar secara fisik di laci kassa?');
    });

    executePaymentSubmit.addEventListener('click', function() {
        hidePaymentConfirmModal();
        paymentForm.submit();
    });
    </script>
    @endpush