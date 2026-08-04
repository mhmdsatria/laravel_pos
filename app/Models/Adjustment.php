<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Adjustment extends Model
{
    use HasFactory;

    protected $table = 'tbl_adjustment';

    protected $fillable = [
        'tgl_adjustment',
        'kode_barang',
        'stok_sistem',
        'stok_fisik',
        'selisih',
        'keterangan',
        'staf_gudang',
        'diverifikasi_oleh',
        'status',
    ];

    protected $casts = [
        'tgl_adjustment' => 'datetime',
        'stok_sistem' => 'float',
        'stok_fisik' => 'float',
        'selisih' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'kode_barang', 'kode_barang');
    }
}
