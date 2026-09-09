<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataTagihan extends Model
{
    use HasFactory;

    protected $table = 'tbl_data_tagihan';

    protected $fillable = [
        'no_do',
        'tgl_dt',
        'sales_id',
        'salesman_name',
        'total_tagihan',
        'total_bayar',
        'status',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'tgl_dt' => 'date',
        'total_tagihan' => 'integer',
        'total_bayar' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (DataTagihan $dataTagihan): void {
            if (blank($dataTagihan->no_do)) {
                $dataTagihan->no_do = static::generateNextNumber();
            }

            if (blank($dataTagihan->tgl_dt)) {
                $dataTagihan->tgl_dt = now();
            }
        });
    }

    public static function generateNextNumber(): string
    {
        $prefix = 'DT/';
        $lastNumber = static::query()
            ->where('no_do', 'like', 'DT/%')
            ->orderByDesc('id')
            ->value('no_do');

        $nextSequence = 104442;

        if (is_string($lastNumber) && preg_match('/DT\/(\d+)/', $lastNumber, $matches)) {
            $nextSequence = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad((string) $nextSequence, 6, '0', STR_PAD_LEFT);
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class, 'sales_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DataTagihanDetail::class, 'data_tagihan_id');
    }
}
