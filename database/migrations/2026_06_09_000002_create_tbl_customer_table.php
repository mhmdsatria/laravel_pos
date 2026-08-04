<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_customer')) {
            Schema::create('tbl_customer', function (Blueprint $table) {
                $table->id();
                $table->string('kode_cuts')->unique();
                $table->string('nama_pelanggan');
                $table->string('no_whatsapp');
                $table->string('email')->nullable();
                $table->enum('kategori', ['Toko', 'Normal', 'Sales'])->default('Normal');
                $table->text('alamat_lengkap')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_customer');
    }
};
