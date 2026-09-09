<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataTagihanDetail extends Model
{
    use HasFactory;

    protected $table = 'tbl_data_tagihan_detail';

    protected $fillable = [
        'data_tagihan_id',
        'penjualan_id',
        'no_invoice',
        'tgl_inv',
        'tgl_jatuh_tempo',
        'nama_toko',
        'nominal_tagihan',
        'bayar',
        'metode_bayar',
        'status',
    ];

    protected $casts = [
        'tgl_inv' => 'date',
        'tgl_jatuh_tempo' => 'date',
        'nominal_tagihan' => 'integer',
        'bayar' => 'integer',
    ];

    public function dataTagihan(): BelongsTo
    {
        return $this->belongsTo(DataTagihan::class, 'data_tagihan_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'penjualan_id');
    }
}
