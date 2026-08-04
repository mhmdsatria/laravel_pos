<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasePayment extends Model
{
    protected $table = 'tbl_pembelian_payment';

    protected $fillable = [
        'pembelian_id',
        'payment_date',
        'amount',
        'payment_method',
        'payment_note',
        'created_by',
    ];

    protected $casts = [
        'pembelian_id' => 'integer',
        'payment_date' => 'date',
        'amount' => 'integer',
        'created_by' => 'integer',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'pembelian_id');
    }
}
