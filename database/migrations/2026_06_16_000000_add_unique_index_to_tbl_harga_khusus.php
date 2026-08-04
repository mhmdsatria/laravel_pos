<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_harga_khusus', function (Blueprint $table): void {
            $table->unique(['type_harga', 'id_target', 'kode_barang'], 'tbl_harga_khusus_target_barang_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_harga_khusus', function (Blueprint $table): void {
            $table->dropUnique('tbl_harga_khusus_target_barang_unique');
        });
    }
};
