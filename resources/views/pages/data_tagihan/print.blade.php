<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DATA TAGIHAN — {{ $tagihan->no_do }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
            font-size: 10px;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 6px;
        }

        .container {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
        }

        /* Header Atas Ringkas (Rata Tengah) */
        .page-title-header {
            text-align: center;
            margin-bottom: 10px;
        }

        .page-title-header h1 {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header-info {
            margin-bottom: 8px;
            font-size: 10px;
            line-height: 1.25;
        }

        .header-info table {
            border-collapse: collapse;
        }

        .header-info td {
            padding: 1px 6px 1px 0;
            vertical-align: top;
        }

        .header-info td.label {
            font-weight: bold;
            width: 100px;
            text-transform: uppercase;
        }

        .header-info td.colon {
            width: 12px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            table-layout: fixed;
        }

        table.data-table th {
            border: 1px solid #000;
            padding: 4px 3px;
            font-size: 8.5px;
            background-color: #f2f2f2;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center !important; /* Judul Header Rata Tengah */
        }

        table.data-table td {
            border: 1px solid #000;
            padding: 4px 3px;
            font-size: 9px;
            line-height: 1.15;
            overflow-wrap: anywhere;
            text-align: left; /* Isi Data Rata Kiri */
        }

        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        .text-left { text-align: left !important; }

        .total-row {
            font-weight: bold;
            background-color: #fdfdfd;
        }

        .footer-signatures {
            margin-top: 20px;
            width: 100%;
            display: flex;
            justify-content: space-between;
        }

        .sig-box {
            text-align: center;
            width: 30%;
        }

        .sig-box .title-sig {
            font-weight: bold;
            margin-bottom: 36px;
            text-transform: uppercase;
            font-size: 9px;
        }

        .sig-box .name-sig {
            font-weight: bold;
            font-size: 10px;
        }

        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            table.data-table th {
                background-color: #f2f2f2 !important;
                -webkit-print-color-adjust: exact;
            }
        }

        .btn-print-action {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #0071E3;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            transition: background 0.2s ease;
        }
        .btn-print-action:hover {
            background: #0077ED;
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="btn-print-action no-print">Cetak Dokumen</button>

    <div class="container">
        <!-- Header Atas (Hanya DATA TAGIHAN di Tengah) -->
        <div class="page-title-header">
            <h1>DATA TAGIHAN</h1>
        </div>

        <!-- Header Info -->
        <div class="header-info">
            <table>
                <tr>
                    <td class="label">NO. TAGIHAN</td>
                    <td class="colon">:</td>
                    <td style="font-weight: bold;">{{ $tagihan->no_do }}</td>
                </tr>
                <tr>
                    <td class="label">TANGGAL</td>
                    <td class="colon">:</td>
                    <td>{{ optional($tagihan->tgl_dt)->translatedFormat('d M Y') }}</td>
                </tr>
                <tr>
                    <td class="label">SALES</td>
                    <td class="colon">:</td>
                    <td style="font-weight: bold;">{{ $tagihan->salesman_name }}</td>
                </tr>
            </table>
        </div>

        <!-- Table Data (Singkatan Bulan 3 Huruf: d M Y) -->
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;">NO</th>
                    <th style="width: 14%;">NO INVOICE</th>
                    <th style="width: 10%;">TGL INV</th>
                    <th style="width: 11%;">JATUH TEMPO</th>
                    <th style="width: 19%;">NAMA TOKO</th>
                    <th style="width: 15%;">NOMINAL TAGIHAN</th>
                    <th style="width: 15%;">BAYAR</th>
                    <th style="width: 12%;">METODE</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tagihan->details as $index => $detail)
                    <tr>
                        <td class="text-left">{{ $index + 1 }}</td>
                        <td class="text-left">{{ $detail->no_invoice }}</td>
                        <td class="text-left">{{ optional($detail->tgl_inv)->translatedFormat('d M Y') ?? '-' }}</td>
                        <td class="text-left">{{ optional($detail->tgl_jatuh_tempo)->translatedFormat('d M Y') ?? '-' }}</td>
                        <td class="text-left" style="text-transform: uppercase;">{{ $detail->nama_toko }}</td>
                        <td class="text-left">Rp {{ number_format($detail->nominal_tagihan, 0, ',', '.') }}</td>
                        <td class="text-left">
                            @if($tagihan->status === 'SELESAI' && $detail->bayar > 0)
                                Rp {{ number_format($detail->bayar, 0, ',', '.') }}
                            @endif
                        </td>
                        <td class="text-left">
                            @if($tagihan->status === 'SELESAI' && $detail->metode_bayar)
                                {{ $detail->metode_bayar }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="5" class="text-center" style="padding: 9px; text-transform: uppercase;">TOTAL TAGIHAN</td>
                    <td class="text-left" style="padding: 9px; font-size: 12px;">Rp {{ number_format($tagihan->total_tagihan, 0, ',', '.') }}</td>
                    <td></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <!-- Footer Signatures -->
        <div class="footer-signatures">
            <div class="sig-box">
                <div class="title-sig">Dibuat Oleh</div>
                <div class="name-sig">( Admin )</div>
            </div>
            <div class="sig-box">
                <div class="title-sig">Dibawa Oleh</div>
                <div class="name-sig">( ........................................ )</div>
            </div>
            <div class="sig-box">
                <div class="title-sig">Diketahui Oleh</div>
                <div class="name-sig">( ........................................ )</div>
            </div>
        </div>
    </div>

</body>
</html>
