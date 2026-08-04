<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_supplier')) {
            Schema::create('tbl_supplier', function (Blueprint $table): void {
                $table->id();
                $table->string('nama_supplier');
                $table->text('alamat');
                $table->string('nama_pic');
                $table->string('no_hp');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tbl_sales')) {
            Schema::create('tbl_sales', function (Blueprint $table): void {
                $table->id();
                $table->string('kode_sales')->unique();
                $table->string('nama_sales');
                $table->enum('status', ['Aktif', 'Nonaktif'])->default('Aktif');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_sales');
        Schema::dropIfExists('tbl_supplier');
    }
};
