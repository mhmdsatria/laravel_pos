<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;


class DeliveryOrderDetail extends Model
{
    protected $table = 'tbl_surat_jalan_detail';
    public $timestamps = false;

    protected $fillable = [
        'surat_jalan_id',
        'kode_barang',
        'qty_kirim',
        'total_belanja',
    ];

    protected function casts(): array
    {
        return [
            'surat_jalan_id' => 'integer',
            'qty_kirim' => 'float',
        ];
    }

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class, 'surat_jalan_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'kode_barang', 'kode_barang');
    }
}
