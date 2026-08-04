<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_surat_jalan')) {
            Schema::create('tbl_surat_jalan', function (Blueprint $table): void {
                $table->id();
                $table->string('no_surat_jalan')->unique();
                $table->unsignedBigInteger('penjualan_id')->index();
                $table->dateTime('tgl_terbit');
                $table->string('nama_sopir');
                $table->string('plat_nomor', 30);
                $table->text('catatan')->nullable();
                $table->enum('status', ['DIPROSES', 'DI JALAN', 'DITERIMA'])->default('DIPROSES');
                $table->string('penerima_lokasi')->nullable();
                $table->timestamps();
                $table->index(['status', 'tgl_terbit']);
            });
        }

        if (! Schema::hasTable('tbl_surat_jalan_detail')) {
            Schema::create('tbl_surat_jalan_detail', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('surat_jalan_id')->index();
                $table->string('kode_barang')->index();
                $table->unsignedInteger('qty_kirim');
                $table->unique(['surat_jalan_id', 'kode_barang'], 'uq_sj_detail_barang');
            });
        }

        if (Schema::hasTable('tbl_penjualan')) {
            try {
                Schema::table('tbl_surat_jalan', function (Blueprint $table): void {
                    $table->foreign('penjualan_id', 'fk_sj_penjualan')
                        ->references('id')->on('tbl_penjualan')
                        ->restrictOnDelete()->cascadeOnUpdate();
                });
            } catch (Throwable) {
            }
        }

        try {
            Schema::table('tbl_surat_jalan_detail', function (Blueprint $table): void {
                $table->foreign('surat_jalan_id', 'fk_sj_detail_header')
                    ->references('id')->on('tbl_surat_jalan')
                    ->cascadeOnDelete()->cascadeOnUpdate();
            });
        } catch (Throwable) {
        }

        if (Schema::hasTable('tbl_barang')) {
            try {
                Schema::table('tbl_surat_jalan_detail', function (Blueprint $table): void {
                    $table->foreign('kode_barang', 'fk_sj_detail_barang')
                        ->references('kode_barang')->on('tbl_barang')
                        ->restrictOnDelete()->cascadeOnUpdate();
                });
            } catch (Throwable) {
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_surat_jalan_detail');
        Schema::dropIfExists('tbl_surat_jalan');
    }
};
