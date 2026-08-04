<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'tbl_customer';

    protected $fillable = [
        'kode_cuts',
        'nama_pelanggan',
        'no_whatsapp',
        'email',
        'kategori',
        'alamat_lengkap',
    ];

    protected static function booted(): void
    {
        static::creating(function (Customer $customer): void {
            if (blank($customer->kode_cuts)) {
                $customer->kode_cuts = static::generateNextKodeCuts();
            }
        });
    }

    public static function generateNextKodeCuts(): string
    {
        $nextNumber = static::query()->count() + 1;

        do {
            $code = 'CUST-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            $exists = static::query()->where('kode_cuts', $code)->exists();
            $nextNumber++;
        } while ($exists);

        return $code;
    }
}
