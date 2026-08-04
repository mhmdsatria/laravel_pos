@extends('layouts.app')

@section('title', 'Dashboard | Toko Bangunan 39')
@section('page_title', 'Dashboard')
@section('active_page', 'dashboard')

@push('head')
<script async src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endpush

@section('content')
<section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-gutter">
    <div class="zenith-card p-lg flex items-center gap-md">
        <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">payments</span>
        </div>
        <div>
            <p class="font-label-md text-label-md text-on-surface-variant mb-1">Total Omset Hari Ini</p>
            <p class="font-headline-md text-headline-md text-on-surface">Rp {{ number_format((float) ($total_omset ?? 0), 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="zenith-card p-lg flex items-center gap-md">
        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-600">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">account_balance_wallet</span>
        </div>
        <div>
            <p class="font-label-md text-label-md text-on-surface-variant mb-1">Piutang Berjalan</p>
            <p class="font-headline-md text-headline-md text-on-surface">Rp {{ number_format((float) ($piutang_berjalan ?? 0), 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="zenith-card p-lg flex items-center gap-md">
        <div class="w-12 h-12 rounded-2xl bg-blue-500/10 flex items-center justify-center text-blue-600">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">receipt_long</span>
        </div>
        <div>
            <p class="font-label-md text-label-md text-on-surface-variant mb-1">Nota Terbit Hari Ini</p>
            <p class="font-headline-md text-headline-md text-on-surface">{{ number_format((int) ($nota_terbit ?? 0), 0, ',', '.') }} Nota</p>
        </div>
    </div>

    <div class="zenith-card p-lg flex items-center gap-md">
        <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-600">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">person</span>
        </div>
        <div class="min-w-0">
            <p class="font-label-md text-label-md text-on-surface-variant mb-1">Pengguna Aktif</p>
            <p class="font-headline-md text-headline-md text-on-surface truncate">{{ $user_aktif ?? 'Admin' }} <span class="text-on-surface-variant font-normal text-sm">(Anda)</span></p>
        </div>
    </div>
</section>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-gutter">
    <div class="lg:col-span-2 zenith-card p-lg flex flex-col min-h-[450px]">
        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-md mb-lg">
            <div>
                <h3 class="font-headline-md text-headline-md text-on-surface">Tren Penjualan</h3>
                <!-- <p class="text-body-md text-on-surface-variant">Omset dan jumlah nota dari <code class="text-primary">tbl_penjualan</code></p> -->
            </div>
            <div class="flex bg-surface-container-low p-1 rounded-lg border border-outline-variant w-fit">
                <a href="?filter=mingguan" class="px-4 py-1.5 rounded-md text-label-md {{ ($filter ?? 'mingguan') === 'mingguan' ? 'bg-white shadow-sm text-primary font-bold' : 'text-on-surface-variant hover:text-on-surface' }} transition-all">Mingguan</a>
                <a href="?filter=bulanan" class="px-4 py-1.5 rounded-md text-label-md {{ ($filter ?? 'mingguan') === 'bulanan' ? 'bg-white shadow-sm text-primary font-bold' : 'text-on-surface-variant hover:text-on-surface' }} transition-all">Bulanan</a>
            </div>
        </div>
        <div class="flex-1 w-full" id="sales-line-chart"></div>
    </div>

    <div class="space-y-gutter">
        <div class="zenith-card overflow-hidden">
            <div class="p-lg border-b border-outline-variant flex justify-between items-center">
                <h3 class="font-headline-md text-headline-md text-on-surface">Stok Kritis</h3>
                <span class="bg-red-100 text-red-600 text-[10px] font-bold uppercase px-2 py-0.5 rounded">Peringatan</span>
            </div>
            <div class="p-lg space-y-md">
                @forelse(($stok_kritis ?? []) as $stok)
                    <div class="flex justify-between items-center p-3 rounded-xl bg-surface-container-lowest border border-outline-variant">
                        <div class="flex items-center gap-sm min-w-0">
                            <div class="w-8 h-8 rounded bg-red-50 flex items-center justify-center text-red-500 shrink-0">
                                <span class="material-symbols-outlined text-[18px]">warning</span>
                            </div>
                            <div class="min-w-0">
                                <span class="font-body-md text-body-md truncate block">{{ $stok->nama_barang ?? 'Barang' }}</span>
                                <span class="text-[10px] text-on-surface-variant">Limit min: {{ (int) ($stok->limit_minimum_stok ?? 0) }}</span>
                            </div>
                        </div>
                        <span class="font-label-md text-label-md text-red-600 font-bold whitespace-nowrap">{{ (int) ($stok->sisa_stok ?? 0) }} {{ $stok->satuan ?? 'Unit' }}</span>
                    </div>
                @empty
                    <div class="p-4 rounded-xl bg-surface-container-low border border-outline-variant text-center text-on-surface-variant">
                        Belum ada data stok kritis.
                    </div>
                @endforelse
            </div>
            <div class="p-lg bg-slate-50/50 border-t border-outline-variant text-center">
                <a href="/product" class="text-primary font-label-md text-label-md font-bold hover:underline">Kelola Inventori</a>
            </div>
        </div>

        <div class="zenith-card p-lg">
            <h3 class="font-headline-md text-headline-md text-on-surface mb-lg">Transaksi Terbaru</h3>
            <div class="space-y-4">
                @forelse(($transaksi_terbaru ?? []) as $trx)
                    @php
                        $isLunas = strtolower((string) ($trx->status ?? 'Lunas')) === 'lunas';
                        $trxDate = $trx->tgl_transaksi ? \Carbon\Carbon::parse($trx->tgl_transaksi)->format('d M Y H:i') : '-';
                    @endphp
                    <div class="flex items-center gap-md pb-4 border-b border-outline-variant last:border-0 last:pb-0">
                        <div class="w-10 h-10 rounded-full {{ $isLunas ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }} flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined">{{ $isLunas ? 'check' : 'schedule' }}</span>
                        </div>
                        <div class="flex-1 overflow-hidden">
                            <p class="font-body-md text-body-md font-bold truncate">{{ $trx->no_invoice ?? 'NO-INVOICE' }} - {{ $trx->nama_pelanggan ?? 'Pelanggan' }}</p>
                            <p class="text-label-sm text-on-surface-variant">{{ $trxDate }} • {{ $trx->metode_bayar ?? '-' }} • {{ $trx->status ?? '-' }}</p>
                        </div>
                        <p class="font-label-md text-label-md font-bold whitespace-nowrap">Rp {{ number_format((float) ($trx->total_belanja ?? 0), 0, ',', '.') }}</p>
                    </div>
                @empty
                    <div class="p-4 rounded-xl bg-surface-container-low border border-outline-variant text-center text-on-surface-variant">
                        Belum ada transaksi terbaru.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const chartDataLabels = @json($chartDataLabels ?? []);
    const chartDataValues = @json($chartDataValues ?? []);
    const chartNotaValues = @json($chartNotaValues ?? []);

    function renderDashboardChart() {
        const chartElement = document.querySelector('#sales-line-chart');
        if (!chartElement || chartElement.dataset.rendered === '1') {
            return;
        }

        if (typeof ApexCharts === 'undefined') {
            chartElement.innerHTML = '<div class="flex h-full min-h-[260px] items-center justify-center rounded-xl border border-outline-variant bg-surface-container-low text-sm font-semibold text-on-surface-variant">Grafik belum dimuat. Data ringkasan tetap tersedia.</div>';
            return;
        }

        chartElement.dataset.rendered = '1';

        const options = {
            series: [
                { name: 'Omset (Rp)', data: chartDataValues },
                { name: 'Jumlah Nota', data: chartNotaValues }
            ],
            chart: {
                height: 350,
                type: 'line',
                toolbar: { show: false },
                animations: { enabled: false },
                fontFamily: 'Inter, Arial, sans-serif'
            },
            stroke: { width: [3, 3], curve: 'straight' },
            markers: { size: 3, hover: { size: 4 } },
            xaxis: {
                categories: chartDataLabels,
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: [
                {
                    title: { text: 'Omset (Rupiah)' },
                    labels: {
                        formatter: function (val) {
                            return 'Rp ' + (Number(val || 0) / 1000000).toFixed(1) + 'jt';
                        }
                    }
                },
                { opposite: true, title: { text: 'Nota' } }
            ],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function (value) {
                        return Number(value || 0).toLocaleString('id-ID');
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                offsetY: -10
            },
            grid: { borderColor: '#f1f1f1' }
        };

        const chart = new ApexCharts(chartElement, options);
        chart.render();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', renderDashboardChart, { once: true });
    } else {
        renderDashboardChart();
    }

    window.addEventListener('load', function () {
        if (typeof ApexCharts !== 'undefined') {
            const chartElement = document.querySelector('#sales-line-chart');
            if (chartElement && chartElement.dataset.rendered !== '1') {
                chartElement.innerHTML = '';
                renderDashboardChart();
            }
        }
    }, { once: true });
})();
</script>
@endpush