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
                $table->unsignedBigInteger('harga_jual_normal')->default(0)->after('harga_beli_terakhir');
            });
        }

        if (! Schema::hasTable('tbl_harga_khusus')) {
            Schema::create('tbl_harga_khusus', function (Blueprint $table): void {
                $table->id();
                $table->enum('type_harga', ['Toko', 'Sales']);
                $table->unsignedBigInteger('id_target');
                $table->string('kode_barang');
                $table->unsignedBigInteger('harga_khusus')->default(0);
                $table->timestamps();
                $table->unique(['type_harga', 'id_target', 'kode_barang'], 'tbl_harga_khusus_unique_target_produk');
                $table->index('kode_barang', 'tbl_harga_khusus_kode_barang_index');
                $table->index(['type_harga', 'id_target'], 'tbl_harga_khusus_target_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_harga_khusus');

        if (Schema::hasTable('tbl_barang') && Schema::hasColumn('tbl_barang', 'harga_jual_normal')) {
            Schema::table('tbl_barang', function (Blueprint $table): void {
                $table->dropColumn('harga_jual_normal');
            });
        }
    }
};
