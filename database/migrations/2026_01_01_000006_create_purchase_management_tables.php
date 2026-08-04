<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_pembelian')) {
            Schema::create('tbl_pembelian', function (Blueprint $table) {
                $table->id();
                $table->string('no_invoice')->unique();
                $table->date('tgl_pembelian');
                $table->unsignedBigInteger('supplier_id');
                $table->string('total_item')->default('0 Jenis / 0 Qty');
                $table->bigInteger('total_harga')->default(0);
                $table->enum('status', ['Selesai', 'Proses', 'Batal'])->default('Selesai');
                $table->timestamps();

                $table->index('tgl_pembelian');
                $table->index('supplier_id');
                $table->index('status');
            });
        }

        if (! Schema::hasTable('tbl_pembelian_detail')) {
            Schema::create('tbl_pembelian_detail', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pembelian_id');
                $table->string('kode_barang');
                $table->integer('qty')->default(0);
                $table->bigInteger('harga_beli')->default(0);
                $table->bigInteger('subtotal')->default(0);
                $table->timestamps();

                $table->index('pembelian_id');
                $table->index('kode_barang');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_pembelian_detail');
        Schema::dropIfExists('tbl_pembelian');
    }
};
