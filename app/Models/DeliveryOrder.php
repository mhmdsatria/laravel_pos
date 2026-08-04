<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryOrder extends Model
{
    protected $table = 'tbl_surat_jalan';

    protected $fillable = [
        'no_surat_jalan',
        'penjualan_id',
        'tgl_terbit',
        'nama_sopir',
        'plat_nomor',
        'catatan',
        'status',
        'penerima_lokasi',
        'total_belanja',
    ];

    protected function casts(): array
    {
        return [
            'tgl_terbit' => 'datetime',
            'penjualan_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DeliveryOrder $order): void {
            $order->no_surat_jalan = $order->no_surat_jalan ?: static::generateNextNumber();
            $order->tgl_terbit = $order->tgl_terbit ?: now();
            $order->plat_nomor = strtoupper(trim((string) $order->plat_nomor));
        });
    }

    public static function generateNextNumber(): string
    {
        $prefix = 'SJ-' . now()->format('Ymd') . '-';
        $last = static::query()
            ->where('no_surat_jalan', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('no_surat_jalan');

        $sequence = 0;
        if (is_string($last) && preg_match('/(\d{4})$/', $last, $matches) === 1) {
            $sequence = (int) $matches[1];
        }

        return $prefix . str_pad((string) ($sequence + 1), 4, '0', STR_PAD_LEFT);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'penjualan_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DeliveryOrderDetail::class, 'surat_jalan_id');
    }
}
