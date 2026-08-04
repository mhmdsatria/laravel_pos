<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseDetail extends Model
{
    protected $table = 'tbl_pembelian_detail';

    protected $fillable = [
        'pembelian_id',
        'kode_barang',
        'qty',
        'harga_beli',
        'subtotal',
    ];

    protected $casts = [
        'pembelian_id' => 'integer',
        'qty' => 'float',
        'harga_beli' => 'integer',
        'subtotal' => 'integer',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'pembelian_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'kode_barang', 'kode_barang');
    }
}
