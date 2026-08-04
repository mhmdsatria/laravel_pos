<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tbl_penjualan', 'alamat_pelanggan')) {
            Schema::table('tbl_penjualan', function (Blueprint $table): void {
                $table->text('alamat_pelanggan')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tbl_penjualan', 'alamat_pelanggan')) {
            Schema::table('tbl_penjualan', function (Blueprint $table): void {
                $table->dropColumn('alamat_pelanggan');
            });
        }
    }
};
