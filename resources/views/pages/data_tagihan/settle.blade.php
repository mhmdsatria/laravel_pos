@extends('layouts.app')

@section('title', 'Input Setoran Data Tagihan | Toko Bangunan 39')

@section('content')
<div class="p-lg space-y-lg flex-1 max-w-6xl mx-auto">

    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-md border-b border-outline-variant/60 pb-md">
        <div>
            <div class="flex items-center gap-2 text-xs text-on-surface-variant mb-1">
                <a href="{{ route('data-tagihan.index') }}" class="hover:text-primary transition-colors">Data Tagihan Sales</a>
                <span>/</span>
                <a href="{{ route('data-tagihan.show', $tagihan->id) }}" class="hover:text-primary transition-colors">{{ $tagihan->no_do }}</a>
                <span>/</span>
                <span class="text-on-surface font-bold">Input Setoran</span>
            </div>
            <h2 class="font-headline-xl text-headline-xl text-on-surface uppercase text-primary font-bold tracking-tight">Input Hasil Setoran Penagihan</h2>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('data-tagihan.show', $tagihan->id) }}" class="flex items-center gap-1 border border-outline-variant bg-white px-4 py-2 rounded-lg text-on-surface-variant hover:bg-surface-container-low text-xs font-bold transition-colors">
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

    <form action="{{ route('data-tagihan.process_settlement', $tagihan->id) }}" method="POST" class="space-y-lg" id="settle-form">
        @csrf

        <!-- Card Header Info -->
        <div class="bg-surface-container-lowest border border-outline-variant p-md rounded-xl shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-md">
            <div>
                <p class="text-on-surface-variant font-bold text-[11px] uppercase tracking-wider">Dokumen & Salesman</p>
                <h3 class="text-lg font-black text-on-surface mt-0.5">{{ $tagihan->no_do }} — {{ $tagihan->salesman_name }}</h3>
                <p class="text-xs text-on-surface-variant mt-1">Tanggal Tagihan: {{ optional($tagihan->tgl_dt)->translatedFormat('d F Y') }}</p>
            </div>

            <div class="text-right bg-surface-container-low/60 p-md rounded-xl border border-outline-variant">
                <p class="text-on-surface-variant font-bold text-[11px] uppercase tracking-wider block">Total Tagihan Yang Dibawa</p>
                <span class="text-xl font-black text-primary">Rp {{ number_format($tagihan->total_tagihan, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Info Banner -->
        <div class="flex items-start gap-2 bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-xs text-blue-800">
            <span class="material-symbols-outlined text-sm mt-0.5 text-blue-500">info</span>
            <span>Masukkan nominal sesuai uang yang diterima dari tiap toko. Boleh sebagian (cicil) atau 0 jika belum bayar. Toko yang belum lunas bisa dimasukkan ke <strong>lembaran tagihan baru</strong> di kunjungan berikutnya.</span>
        </div>

        <!-- Panel Tabel Input Pembayaran -->
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">
            <div class="px-4 py-3 border-b border-outline-variant flex flex-col sm:flex-row justify-between sm:items-center gap-2 bg-surface-container-low/40">
                <div>
                    <h4 class="text-sm font-black text-on-surface tracking-tight">Rincian Pembayaran Per Toko</h4>
                    <p class="text-[11px] text-on-surface-variant">Isi 0 atau kosongkan jika toko belum bayar. Boleh dicicil sebagian.</p>
                </div>

                <button type="button" id="btnFillAllLunas" class="flex items-center gap-1 rounded-xl border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-800 hover:bg-emerald-100 transition-colors">
                    <span class="material-symbols-outlined text-sm">done_all</span>
                    <span>Set Semua Lunas</span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant text-on-surface-variant font-bold">
                            <th class="px-4 py-3 w-10 text-center">No</th>
                            <th class="px-4 py-3">No. Invoice</th>
                            <th class="px-4 py-3">Nama Toko</th>
                            <th class="px-4 py-3 text-right">Tagihan (Rp)</th>
                            <th class="px-4 py-3 text-center w-56">Jumlah Dibayar (Rp)</th>
                            <th class="px-4 py-3 text-center w-36">Metode Bayar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/60">
                        @foreach($tagihan->details as $index => $detail)
                            <tr class="hover:bg-surface-container-low/30 transition-colors">
                                <td class="px-4 py-3.5 text-center font-bold text-on-surface-variant/80">{{ $index + 1 }}</td>
                                <td class="px-4 py-3.5 font-mono font-bold text-primary tracking-wide">
                                    {{ $detail->no_invoice }}
                                    <input type="hidden" name="payments[{{ $index }}][detail_id]" value="{{ $detail->id }}">
                                </td>
                                <td class="px-4 py-3.5 font-semibold text-on-surface">{{ $detail->nama_toko }}</td>
                                <td class="px-4 py-3.5 text-right font-black text-on-surface">
                                    Rp {{ number_format($detail->nominal_tagihan, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-on-surface-variant">Rp</span>
                                        {{-- Hidden field angka murni untuk backend --}}
                                        <input type="hidden" name="payments[{{ $index }}][bayar]" class="hidden-bayar" value="0">
                                        {{-- Display input dengan format titik ribuan --}}
                                        <input type="text" inputmode="numeric" autocomplete="off"
                                            class="input-bayar-display h-9 w-full rounded-xl border border-outline-variant bg-white pl-8 pr-3 text-xs font-bold text-on-surface text-right outline-none focus:border-primary focus:ring-2 focus:ring-primary/10"
                                            placeholder="0"
                                            data-max="{{ $detail->nominal_tagihan }}">
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <select name="payments[{{ $index }}][metode_bayar]" class="h-9 w-full rounded-xl border border-outline-variant bg-white px-3 text-xs font-bold text-on-surface outline-none focus:border-primary focus:ring-2 focus:ring-primary/10">
                                        <option value="CASH">CASH</option>
                                        <option value="TRANSFER">TRANSFER</option>
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Total Collected -->
            <div class="p-md bg-emerald-50/60 border-t border-emerald-200 flex items-center justify-between">
                <span class="text-xs font-bold text-emerald-900 uppercase tracking-wider">Total Setoran Diterima Kali Ini:</span>
                <span id="totalSetoranText" class="text-xl font-black text-emerald-700">Rp 0</span>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('data-tagihan.show', $tagihan->id) }}" class="flex items-center gap-1 border border-outline-variant bg-white px-4 py-2.5 rounded-lg text-on-surface-variant hover:bg-surface-container-low text-xs font-bold transition-colors">
                <span>Batal</span>
            </a>
            <button type="submit" class="flex items-center gap-1.5 bg-emerald-600 text-white px-5 py-2.5 rounded-lg text-xs font-bold hover:bg-emerald-700 shadow-sm transition-all">
                <span class="material-symbols-outlined text-base">check_circle</span>
                <span>Simpan Setoran & Tutup Lembaran</span>
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const displayInputs = document.querySelectorAll('.input-bayar-display');
    const hiddenInputs = document.querySelectorAll('.hidden-bayar');
    const totalSetoranText = document.getElementById('totalSetoranText');
    const btnFillAllLunas = document.getElementById('btnFillAllLunas');

    function formatRibuan(num) {
        if (!num || num === 0) return '';
        return parseInt(num, 10).toLocaleString('id-ID');
    }

    function calculateTotal() {
        let sum = 0;
        hiddenInputs.forEach(hidden => {
            const val = parseInt(hidden.value || '0', 10);
            if (val > 0) sum += val;
        });
        totalSetoranText.textContent = `Rp ${sum.toLocaleString('id-ID')}`;
    }

    displayInputs.forEach((input, idx) => {
        input.addEventListener('input', function() {
            const raw = this.value.replace(/[^0-9]/g, '');
            const maxVal = parseInt(this.getAttribute('data-max') || '0', 10);
            let numVal = parseInt(raw, 10) || 0;

            if (numVal > maxVal) numVal = maxVal;

            this.value = numVal > 0 ? numVal.toLocaleString('id-ID') : '';
            hiddenInputs[idx].value = numVal;
            calculateTotal();
        });

        input.addEventListener('focus', function() {
            const raw = this.value.replace(/\./g, '');
            this.value = raw === '0' ? '' : raw;
        });

        input.addEventListener('blur', function() {
            const numVal = parseInt(this.value.replace(/[^0-9]/g, ''), 10) || 0;
            this.value = numVal > 0 ? numVal.toLocaleString('id-ID') : '';
            hiddenInputs[idx].value = numVal;
            calculateTotal();
        });
    });

    btnFillAllLunas.addEventListener('click', function() {
        displayInputs.forEach((input, idx) => {
            const maxVal = parseInt(input.getAttribute('data-max') || '0', 10);
            input.value = maxVal > 0 ? maxVal.toLocaleString('id-ID') : '';
            hiddenInputs[idx].value = maxVal;
        });
        calculateTotal();
    });

    calculateTotal();
});
</script>
@endpush
@endsection
