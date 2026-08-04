<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Purchase extends Model
{
    protected $table = 'tbl_pembelian';

    protected $fillable = [
        'no_invoice',
        'tgl_pembelian',
        'supplier_id',
        'total_item',
        'total_harga',
        'paid_total',
        'remaining_total',
        'payment_status',
        'status',
        'note',
    ];

    protected $casts = [
        'tgl_pembelian' => 'date',
        'supplier_id' => 'integer',
        'total_harga' => 'integer',
        'paid_total' => 'integer',
        'remaining_total' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Purchase $purchase): void {
            if (empty($purchase->no_invoice)) {
                $purchase->no_invoice = static::generateNextInvoice();
            }

            if (empty($purchase->tgl_pembelian)) {
                $purchase->tgl_pembelian = now()->toDateString();
            }

            if (empty($purchase->status)) {
                $purchase->status = 'Selesai';
            }

            if (empty($purchase->payment_status)) {
                $purchase->payment_status = 'Belum Dibayar';
            }
        });
    }

    public static function generateNextInvoice(): string
    {
        $period = Carbon::now()->format('Ym');
        $prefix = 'INV/P/' . $period . '/';

        $existingInvoices = static::query()
            ->where('no_invoice', 'like', $prefix . '%')
            ->pluck('no_invoice')
            ->all();

        $maxCounter = 0;

        foreach ($existingInvoices as $invoice) {
            $counterText = str_replace($prefix, '', (string) $invoice);
            $counterNumber = (int) preg_replace('/[^0-9]/', '', $counterText);
            if ($counterNumber > $maxCounter) {
                $maxCounter = $counterNumber;
            }
        }

        return $prefix . str_pad((string) ($maxCounter + 1), 4, '0', STR_PAD_LEFT);
    }

    public function details(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class, 'pembelian_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class, 'pembelian_id')->orderBy('payment_date')->orderBy('id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
