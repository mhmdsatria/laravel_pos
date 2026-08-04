<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pricing extends Model
{
    use HasFactory;

    protected $table = 'tbl_harga_khusus';

    protected $fillable = [
        'type_harga',
        'id_target',
        'kode_barang',
        'harga_khusus',
    ];

    protected $casts = [
        'id_target' => 'integer',
        'harga_khusus' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'kode_barang', 'kode_barang');
    }
}
