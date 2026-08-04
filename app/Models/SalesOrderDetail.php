<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrderDetail extends Model
{
    use HasFactory;

    protected $table = 'tbl_penjualan_detail';

    protected $fillable = [
        'penjualan_id',
        'kode_barang',
        'qty',
        'qty_terkirim',
        'qty_refund',
        'harga_jual',
        'subtotal',
    ];

    protected $casts = [
        'qty' => 'float',
        'qty_terkirim' => 'float',
        'qty_refund' => 'float',
        'harga_jual' => 'integer',
        'subtotal' => 'integer',
    ];

    protected $attributes = [
        'qty_terkirim' => 0,
        'qty_refund' => 0,
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'penjualan_id', 'id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'kode_barang', 'kode_barang');
    }


    public function refundDetails(): HasMany
    {
        return $this->hasMany(SalesRefundDetail::class, 'penjualan_detail_id', 'id');
    }

}
