<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_barang') && ! Schema::hasColumn('tbl_barang', 'harga_jual_normal')) {
            Schema::table('tbl_barang', function (Blueprint $table): void {
                $table->bigInteger('harga_jual_normal')->default(0)->after('harga_beli_terakhir');
            });
        }

        if (! Schema::hasTable('tbl_harga_khusus')) {
            Schema::create('tbl_harga_khusus', function (Blueprint $table): void {
                $table->id();
                $table->enum('type_harga', ['Toko', 'Sales']);
                $table->unsignedBigInteger('id_target');
                $table->string('kode_barang');
                $table->bigInteger('harga_khusus')->default(0);
                $table->timestamps();
                $table->unique(['type_harga', 'id_target', 'kode_barang'], 'tbl_harga_khusus_unique_target_barang');
                $table->index('kode_barang');
            });
        }

        if (Schema::hasTable('tbl_penjualan') && ! Schema::hasColumn('tbl_penjualan', 'sisa_piutang')) {
            Schema::table('tbl_penjualan', function (Blueprint $table): void {
                $table->bigInteger('sisa_piutang')->default(0)->after('total_belanja');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbl_penjualan') && Schema::hasColumn('tbl_penjualan', 'sisa_piutang')) {
            Schema::table('tbl_penjualan', function (Blueprint $table): void {
                $table->dropColumn('sisa_piutang');
            });
        }
    }
};
