@extends('layouts.app')

@section('title', 'Detail Data Tagihan ' . $tagihan->no_do . ' | Toko Bangunan 39')

@section('content')
<div class="p-lg space-y-lg flex-1 max-w-6xl mx-auto">

    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-md border-b border-outline-variant/60 pb-md">
        <div>
            <div class="flex items-center gap-2 text-xs text-on-surface-variant mb-1">
                <a href="{{ route('data-tagihan.index') }}" class="hover:text-primary transition-colors">Data Tagihan Sales</a>
                <span>/</span>
                <span class="text-on-surface font-bold">{{ $tagihan->no_do }}</span>
            </div>
            <h2 class="font-headline-xl text-headline-xl text-on-surface uppercase text-primary font-bold tracking-tight">Detail Dokumen Tagihan</h2>
        </div>
        
        <div class="flex items-center gap-2">
            <a href="{{ route('data-tagihan.print', $tagihan->id) }}" target="_blank" class="flex items-center gap-1.5 border border-outline-variant bg-surface-container-lowest px-4 py-2 rounded-lg text-on-surface-variant hover:bg-surface-container-low text-xs font-bold transition-colors">
                <span class="material-symbols-outlined text-md">print</span>
                <span>Cetak Lembaran</span>
            </a>
            @if($tagihan->status === 'DIBAWA')
                <a href="{{ route('data-tagihan.settle', $tagihan->id) }}" class="flex items-center gap-1.5 bg-emerald-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-emerald-700 shadow-sm transition-all">
                    <span class="material-symbols-outlined text-md">payments</span>
                    <span>Input Setoran</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Card Summary Header -->
    <div class="bg-surface-container-lowest border border-outline-variant p-md rounded-xl shadow-sm">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-md">
            <div>
                <p class="text-on-surface-variant font-bold text-[11px] uppercase tracking-wider">No. DO Tagihan</p>
                <h3 class="text-base font-black text-primary font-mono mt-1">{{ $tagihan->no_do }}</h3>
            </div>
            <div>
                <p class="text-on-surface-variant font-bold text-[11px] uppercase tracking-wider">Tanggal Penagihan</p>
                <h3 class="text-base font-bold text-on-surface mt-1">{{ optional($tagihan->tgl_dt)->translatedFormat('d F Y') }}</h3>
            </div>
            <div>
                <p class="text-on-surface-variant font-bold text-[11px] uppercase tracking-wider">Salesman Bertugas</p>
                <h3 class="text-base font-bold text-on-surface mt-1">{{ $tagihan->salesman_name }}</h3>
            </div>
            <div>
                <p class="text-on-surface-variant font-bold text-[11px] uppercase tracking-wider">Status Dokumen</p>
                <div class="mt-1">
                    @if($tagihan->status === 'SELESAI')
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase tracking-wider">
                            SELESAI DISETORKAN
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800 uppercase tracking-wider">
                            DIBAWA SALES
                        </span>
                    @endif
                </div>
            </div>
        </div>

        @if($tagihan->catatan)
            <div class="mt-md pt-md border-t border-outline-variant/60 text-xs text-on-surface-variant">
                <strong class="text-on-surface">Catatan:</strong> {{ $tagihan->catatan }}
            </div>
        @endif
    </div>

    <!-- Panel Tabel Details -->
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">
        <div class="px-4 py-3 border-b border-outline-variant bg-surface-container-low/40">
            <h4 class="text-sm font-black text-on-surface tracking-tight">Daftar Piutang Toko Yang Dibawa</h4>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant text-on-surface-variant font-bold">
                        <th class="px-4 py-3 w-12 text-center">No</th>
                        <th class="px-4 py-3">No. Invoice</th>
                        <th class="px-4 py-3">Tgl Invoice</th>
                        <th class="px-4 py-3">Jatuh Tempo</th>
                        <th class="px-4 py-3">Nama Toko</th>
                        <th class="px-4 py-3 text-right">Nominal Tagihan</th>
                        <th class="px-4 py-3 text-right">Dibayar (Real)</th>
                        <th class="px-4 py-3 text-center">Metode</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60">
                    @foreach($tagihan->details as $index => $detail)
                        <tr class="hover:bg-surface-container-low/30 transition-colors">
                            <td class="px-4 py-3.5 text-center font-bold text-on-surface-variant/80">{{ $index + 1 }}</td>
                            <td class="px-4 py-3.5 font-mono font-bold text-primary tracking-wide">{{ $detail->no_invoice }}</td>
                            <td class="px-4 py-3.5 text-on-surface-variant">{{ optional($detail->tgl_inv)->translatedFormat('d M Y') ?? '-' }}</td>
                            <td class="px-4 py-3.5 text-on-surface-variant">{{ optional($detail->tgl_jatuh_tempo)->translatedFormat('d M Y') ?? '-' }}</td>
                            <td class="px-4 py-3.5 font-semibold text-on-surface">{{ $detail->nama_toko }}</td>
                            <td class="px-4 py-3.5 text-right font-black text-on-surface">
                                Rp {{ number_format($detail->nominal_tagihan, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-black {{ $detail->bayar > 0 ? 'text-emerald-600' : 'text-on-surface-variant/50' }}">
                                @if($tagihan->status === 'SELESAI')
                                    Rp {{ number_format($detail->bayar, 0, ',', '.') }}
                                @else
                                    <span class="text-on-surface-variant/50 font-normal italic">(Diisi saat setoran)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if($detail->metode_bayar)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-surface-container-high text-on-surface-variant uppercase tracking-wider">
                                        {{ $detail->metode_bayar }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-surface-container-low/60 font-bold border-t border-outline-variant">
                        <td colspan="5" class="px-4 py-3.5 text-right text-on-surface-variant font-bold text-[11px] uppercase tracking-wider">TOTAL TAGIHAN YANG DI BAWA:</td>
                        <td class="px-4 py-3.5 text-right text-primary text-sm font-black">
                            Rp {{ number_format($tagihan->total_tagihan, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3.5 text-right text-emerald-600 text-sm font-black">
                            @if($tagihan->status === 'SELESAI')
                                Rp {{ number_format($tagihan->total_bayar, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
