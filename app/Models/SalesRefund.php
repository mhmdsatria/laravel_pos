<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class SalesRefund extends Model
{
    use HasFactory;

    protected $table = 'tbl_penjualan_refund';

    protected $fillable = [
        'penjualan_id',
        'no_refund',
        'refund_at',
        'refund_total',
        'refund_reason',
        'refunded_by',
    ];

    protected $casts = [
        'refund_at' => 'datetime',
        'refund_total' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (SalesRefund $refund): void {
            if (blank($refund->no_refund)) {
                $refund->no_refund = static::generateRefundNumber();
            }

            if (blank($refund->refund_at)) {
                $refund->refund_at = now();
            }
        });
    }

    public static function generateRefundNumber(): string
    {
        $period = Carbon::now()->format('Ym');
        $prefix = 'RFN/'.$period.'/';

        $lastNumber = static::query()
            ->where('no_refund', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('no_refund');

        $nextNumber = 1;

        if (is_string($lastNumber) && str_contains($lastNumber, '/')) {
            $parts = explode('/', $lastNumber);
            $nextNumber = ((int) end($parts)) + 1;
        }

        return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'penjualan_id', 'id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(SalesRefundDetail::class, 'refund_id', 'id');
    }
}
