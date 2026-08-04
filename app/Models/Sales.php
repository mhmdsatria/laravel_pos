<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sales extends Model
{
    use HasFactory;

    protected $table = 'tbl_sales';

    protected $fillable = [
        'kode_sales',
        'nama_sales',
        'status',
        'total_belanja',
    ];

    protected static function booted(): void
    {
        static::creating(function (Sales $sales): void {
            if (blank($sales->kode_sales)) {
                $sales->kode_sales = static::generateNextKodeSales();
            }

            if (blank($sales->status)) {
                $sales->status = 'Aktif';
            }
        });
    }

    public static function generateNextKodeSales(): string
    {
        $maxNumber = static::query()
            ->whereNotNull('kode_sales')
            ->pluck('kode_sales')
            ->map(function ($kode): int {
                if (is_string($kode) && preg_match('/^SLS-(\d+)$/', $kode, $matches)) {
                    return (int) $matches[1];
                }

                return 0;
            })
            ->max();

        $nextNumber = ((int) $maxNumber) + 1;

        return 'SLS-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
