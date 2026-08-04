<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // PENGAMAN: Jika nama barang kosong, lewati baris ini
        if (empty($row['nama_barang'])) {
            return null;
        }

        $mode = trim(strtolower($row['kode_mode'] ?? 'otomatis'));
        $stokAwal = (float) str_replace(',', '.', (string) ($row['stok_awal'] ?? 0));
        
        // Jika manual, ambil kodenya. Jika otomatis, set null agar digenerate oleh sistem
        $kodeBarang = $mode === 'manual' ? trim((string) ($row['kode_barang'] ?? '')) : null;

        // Validasi opsional: Jika manual tapi di Excel kodenya kosong, lewati baris
        if ($mode === 'manual' && empty($kodeBarang)) {
            return null; 
        }

        // Bersihkan nominal harga dari karakter non-angka (Simbol Rp, titik, atau koma)
        $hargaBeli = preg_replace('/[^0-9]/', '', (string) ($row['harga_beli_terakhir'] ?? 0));
        $hargaJualNormal = preg_replace('/[^0-9]/', '', (string) ($row['harga_jual_normal'] ?? 0));

        return new Product([
            'kode_barang'         => $kodeBarang,
            'nama_barang'         => trim((string) $row['nama_barang']),
            'deskripsi'           => $row['deskripsi'] ?? null,
            'satuan'              => trim((string) $row['satuan']),
            'harga_beli_terakhir' => (int) $hargaBeli,
            'harga_jual_normal'   => (int) $hargaJualNormal,
            'stok_awal'           => $stokAwal,
            'sisa_stok'           => $stokAwal, // Sisa stok berjalan disamakan di awal
            'limit_minimum_stok'  => (float) str_replace(',', '.', (string) ($row['limit_minimum_stok'] ?? 0)),
        ]);
    }
}