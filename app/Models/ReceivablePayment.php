<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceivablePayment extends Model
{
    use HasFactory;

    protected $table = 'tbl_piutang_cicilan';

    protected $fillable = [
        'penjualan_id',
        'tgl_bayar',
        'nominal',
        'penerima_kasir',
    ];

    protected $casts = [
        'tgl_bayar' => 'date',
        'nominal' => 'integer',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'penjualan_id');
    }
}
