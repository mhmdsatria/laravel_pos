<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_data_tagihan', function (Blueprint $table) {
            $table->id();
            $table->string('no_do')->unique();
            $table->date('tgl_dt');
            $table->foreignId('sales_id')->nullable()->constrained('tbl_sales')->nullOnDelete();
            $table->string('salesman_name');
            $table->bigInteger('total_tagihan')->default(0);
            $table->bigInteger('total_bayar')->default(0);
            $table->string('status')->default('DIBAWA'); // DIBAWA, SELESAI, BATAL
            $table->text('catatan')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('tbl_data_tagihan_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_tagihan_id')->constrained('tbl_data_tagihan')->cascadeOnDelete();
            $table->foreignId('penjualan_id')->constrained('tbl_penjualan')->cascadeOnDelete();
            $table->string('no_invoice');
            $table->date('tgl_inv')->nullable();
            $table->date('tgl_jatuh_tempo')->nullable();
            $table->string('nama_toko');
            $table->bigInteger('nominal_tagihan')->default(0);
            $table->bigInteger('bayar')->nullable();
            $table->string('metode_bayar')->nullable(); // CASH, TRANSFER
            $table->string('status')->default('DIBAWA'); // DIBAWA, LUNAS, CICIL, TIDAK_BAYAR
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_data_tagihan_detail');
        Schema::dropIfExists('tbl_data_tagihan');
    }
};
