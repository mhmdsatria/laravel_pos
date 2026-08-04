<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesRefundDetail extends Model
{
    use HasFactory;

    protected $table = 'tbl_penjualan_refund_detail';

    protected $fillable = [
        'refund_id',
        'penjualan_detail_id',
        'kode_barang',
        'qty_refund',
        'harga_jual',
        'subtotal_refund',
    ];

    protected $casts = [
        'qty_refund' => 'float',
        'harga_jual' => 'integer',
        'subtotal_refund' => 'integer',
    ];

    public function refund(): BelongsTo
    {
        return $this->belongsTo(SalesRefund::class, 'refund_id', 'id');
    }

    public function orderDetail(): BelongsTo
    {
        return $this->belongsTo(SalesOrderDetail::class, 'penjualan_detail_id', 'id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'kode_barang', 'kode_barang');
    }
}
