<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tbl_penjualan_detail', 'qty_terkirim')) {
            Schema::table('tbl_penjualan_detail', function (Blueprint $table): void {
                $table->unsignedInteger('qty_terkirim')
                    ->default(0)
                    ->after('qty');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tbl_penjualan_detail', 'qty_terkirim')) {
            Schema::table('tbl_penjualan_detail', function (Blueprint $table): void {
                $table->dropColumn('qty_terkirim');
            });
        }
    }
};
