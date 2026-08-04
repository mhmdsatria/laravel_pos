<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;


class SalesOrder extends Model
{
    use HasFactory;

    protected $table = 'tbl_penjualan';

    protected $fillable = [
        'no_invoice',
        'tgl_transaksi',
        'tipe_pelanggan',
        'nama_pelanggan',
        'alamat_pelanggan',
        'customer_id',
        'sales_id',
        'total_belanja',
        'refund_total',
        'sisa_piutang',
        'metode_bayar',
        'status',
        'refund_at',
        'refund_reason',
        'refunded_by',
        'jatuh_tempo',
        'cashier_name',
    ];

    protected $casts = [
        'tgl_transaksi' => 'datetime',
        'jatuh_tempo' => 'date',
        'refund_at' => 'datetime',
        'total_belanja' => 'integer',
        'refund_total' => 'integer',
        'sisa_piutang' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (SalesOrder $order): void {
            if (blank($order->no_invoice)) {
                $order->no_invoice = static::generateInvoiceNumber();
            }

            if (blank($order->tgl_transaksi)) {
                $order->tgl_transaksi = now();
            }

            if ($order->metode_bayar === 'TEMPO') {
                $order->status = 'Tempo';
                if ($order->sisa_piutang === null || (int) $order->sisa_piutang === 0) {
                    $order->sisa_piutang = (int) $order->total_belanja;
                }
            }

            if ($order->metode_bayar === 'CASH') {
                $order->status = 'Lunas';
                $order->sisa_piutang = 0;
            }
        });
    }

    public static function generateInvoiceNumber(): string
    {
        $period = Carbon::now()->format('Ym');
        $prefix = '#SLS/'.$period.'/';

        $lastInvoice = static::query()
            ->where('no_invoice', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('no_invoice');

        $nextNumber = 1;

        if (is_string($lastInvoice) && str_contains($lastInvoice, '/')) {
            $parts = explode('/', $lastInvoice);
            $lastNumber = (int) end($parts);
            $nextNumber = $lastNumber + 1;
        }

        return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function details(): HasMany
    {
        return $this->hasMany(SalesOrderDetail::class, 'penjualan_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class, 'sales_id');
    }


    public function refunds(): HasMany
    {
        return $this->hasMany(SalesRefund::class, 'penjualan_id', 'id')->orderByDesc('refund_at')->orderByDesc('id');
    }

    public function receivablePayments(): HasMany
    {
        return $this->hasMany(ReceivablePayment::class, 'penjualan_id');
    }
}
