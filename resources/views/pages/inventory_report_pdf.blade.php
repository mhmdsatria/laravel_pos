<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Stok Barang</title>
    <style>
        /* Base Style - Modern & Clean */
        body { 
            font-family: 'Inter', system-ui, -apple-system, sans-serif; 
            font-size: 11px; 
            color: #1f2937; 
            line-height: 1.4;
            margin: 20px;
        }
        
        /* Header Section */
        .header { 
            margin-bottom: 20px; 
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 12px;
        }
        .header h2 { 
            margin: 0; 
            font-size: 16px; 
            font-weight: 700;
            color: #064e3b; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header p { 
            margin: 4px 0 0 0; 
            font-size: 9px; 
            color: #6b7280; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Teks Ringkasan (Simple) */
        .summary-text {
            font-size: 11px;
            color: #374151;
            margin-bottom: 12px;
        }
        .summary-text strong {
            color: #064e3b;
        }

        /* Table Data Style */
        .table-data { 
            width: 100%; 
            border-collapse: collapse; 
        }
        .table-data th { 
            background: #064e3b; 
            color: #ffffff; 
            padding: 8px 10px; 
            text-align: left; 
            font-size: 10px; 
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .table-data td { 
            padding: 8px 10px; 
            border-bottom: 1px solid #e5e7eb; 
            color: #000000;
            vertical-align: middle;
        }
        /* Zebra Striping Halus */
        .table-data tbody tr:nth-child(even) {
            background-color: #ffff;
        }

        /* Utility Classes */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: 10px; color: #4b5563; }
        .product-name { font-weight: 500; color: #000000; }

        /* Modern Soft Pill Badges */
        .badge {
            display: inline-block;
            padding: 3px 6px;
            font-size: 9px;
            font-weight: 700;
            border-radius: 4px;
            text-align: center;
            letter-spacing: 0.5px;
        }
        .badge-out { color: #000; font-weight: bold; }
        .badge-critical { color: #000; font-weight: bold; }
        .badge-available { color: #000; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Laporan Stok Barang Real-Time</h2>
        <p>Toko Bangunan 39 • Diunduh: {{ $date }}</p>
    </div>

    <div class="summary-text">
        Total SKU: <strong>{{ number_format($totalJenisBarang, 0, ',', '.') }} Jenis</strong>
    </div>

    <table class="table-data">
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="15%">Kode Barang</th>
                <th>Nama Barang</th>
                <th width="12%">Satuan</th>
                <th width="15%" class="text-right">Stok Aktif</th>
                <th width="15%" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $index => $item)
                @php
                    $stock = (float)($item->sisa_stok ?? 0);
                    $limit = (float)($item->limit_minimum_stok ?? 0);
                @endphp
                <tr>
                    <td class="text-center" style="color: #9ca3af;">{{ $index + 1 }}</td>
                    <td class="font-mono">{{ $item->kode_barang }}</td>
                    <td class="product-name">{{ $item->nama_barang }}</td>
                    <td>{{ $item->satuan }}</td>
                    <td class="text-right" style="font-weight: 500;">{{ format_qty($stock) }}</td>
                    <td class="text-center">
                        @if ($stock === 0)
                            <span class="badge badge-out">HABIS</span>
                        @elseif ($stock <= $limit)
                            <span class="badge badge-critical">KRITIS</span>
                        @else
                            <span class="badge badge-available">TERSEDIA</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>