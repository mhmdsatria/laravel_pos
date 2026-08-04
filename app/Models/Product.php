<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Product extends Model
{
    protected $table = 'tbl_barang';

    protected $fillable = [
        'kode_barang',
        'nama_barang',
        'deskripsi',
        'satuan',
        'harga_beli_terakhir',
        'harga_jual_normal',
        'stok_awal',
        'sisa_stok',
        'limit_minimum_stok',
    ];

    protected $casts = [
        'harga_beli_terakhir' => 'integer',
        'harga_jual_normal' => 'integer',
        'stok_awal' => 'float',
        'sisa_stok' => 'float',
        'limit_minimum_stok' => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            $kode = trim((string) $product->kode_barang);

            if ($kode === '' || strtoupper($kode) === 'AUTO' || strtoupper($kode) === 'OTOMATIS') {
                $product->kode_barang = static::generateNextKodeBarang();
            }

            if ($product->harga_beli_terakhir === null || $product->harga_beli_terakhir === '') {
                $product->harga_beli_terakhir = 0;
            }

            if ($product->harga_jual_normal === null || $product->harga_jual_normal === '') {
                $product->harga_jual_normal = 0;
            }

            if ($product->stok_awal === null || $product->stok_awal === '') {
                $product->stok_awal = 0;
            }

            if ($product->sisa_stok === null || $product->sisa_stok === '') {
                $product->sisa_stok = (float) $product->stok_awal;
            }

            if ($product->limit_minimum_stok === null || $product->limit_minimum_stok === '') {
                $product->limit_minimum_stok = 0;
            }
        });

        static::updating(function (Product $product): void {
            // Perlindungan berlapis: apabila suatu form lama tidak mengirim kolom stok,
            // jangan pernah mengubah stok aktif menjadi nol/null saat data master disimpan.
            foreach (['stok_awal', 'sisa_stok', 'limit_minimum_stok'] as $field) {
                $value = $product->getAttribute($field);
                if ($value === null || $value === '') {
                    $product->setAttribute($field, (float) $product->getOriginal($field));
                }
            }
            foreach (['harga_beli_terakhir', 'harga_jual_normal'] as $field) {
                $value = $product->getAttribute($field);
                if ($value === null || $value === '') {
                    $product->setAttribute($field, (int) $product->getOriginal($field));
                }
            }
        });

        static::saved(function (Product $product): void {
            static::syncStockRow($product);
        });

        static::deleted(function (Product $product): void {
            if (Schema::hasTable('stok_barang')) {
                DB::table('stok_barang')
                    ->where('kode_barang', $product->kode_barang)
                    ->delete();
            }
        });
    }

    public static function generateNextKodeBarang(): string
    {
        if (! Schema::hasTable('tbl_barang')) {
            return 'BRG-0001';
        }

        $codes = static::query()
            ->whereNotNull('kode_barang')
            ->where('kode_barang', 'like', 'BRG-%')
            ->pluck('kode_barang');

        $maxNumber = 0;

        foreach ($codes as $code) {
            $code = (string) $code;
            if (preg_match('/^BRG-(\d+)$/', $code, $matches)) {
                $number = (int) $matches[1];
                if ($number > $maxNumber) {
                    $maxNumber = $number;
                }
            }
        }

        return 'BRG-' . str_pad((string) ($maxNumber + 1), 4, '0', STR_PAD_LEFT);
    }

    public static function syncStockRow(Product $product): void
    {
        if (! Schema::hasTable('stok_barang')) {
            return;
        }

        $now = now()->format('Y-m-d H:i:s');

        $match = [
            'kode_barang' => $product->kode_barang,
        ];

        $values = [
            'nama_barang' => $product->nama_barang,
            'stok_aktif' => (float) $product->sisa_stok,
        ];

        if (Schema::hasColumn('stok_barang', 'updated_at')) {
            $values['updated_at'] = $now;
        }

        if (Schema::hasColumn('stok_barang', 'created_at')) {
            $exists = DB::table('stok_barang')
                ->where('kode_barang', $product->kode_barang)
                ->exists();

            if (! $exists) {
                $values['created_at'] = $now;
            }
        }

        DB::table('stok_barang')->updateOrInsert($match, $values);
    }
}
