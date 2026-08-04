<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_barang')) {
            Schema::create('tbl_barang', function (Blueprint $table): void {
                $table->id();
                $table->string('kode_barang')->unique();
                $table->string('nama_barang');
                $table->text('deskripsi')->nullable();
                $table->string('satuan');
                $table->unsignedBigInteger('harga_beli_terakhir')->default(0);
                $table->integer('stok_awal')->default(0);
                $table->integer('sisa_stok')->default(0);
                $table->integer('limit_minimum_stok')->default(0);
                $table->timestamps();
            });
        } else {
            Schema::table('tbl_barang', function (Blueprint $table): void {
                if (! Schema::hasColumn('tbl_barang', 'kode_barang')) {
                    $table->string('kode_barang')->unique()->after('id');
                }
                if (! Schema::hasColumn('tbl_barang', 'nama_barang')) {
                    $table->string('nama_barang')->after('kode_barang');
                }
                if (! Schema::hasColumn('tbl_barang', 'deskripsi')) {
                    $table->text('deskripsi')->nullable()->after('nama_barang');
                }
                if (! Schema::hasColumn('tbl_barang', 'satuan')) {
                    $table->string('satuan')->after('deskripsi');
                }
                if (! Schema::hasColumn('tbl_barang', 'harga_beli_terakhir')) {
                    $table->unsignedBigInteger('harga_beli_terakhir')->default(0)->after('satuan');
                }
                if (! Schema::hasColumn('tbl_barang', 'stok_awal')) {
                    $table->integer('stok_awal')->default(0)->after('harga_beli_terakhir');
                }
                if (! Schema::hasColumn('tbl_barang', 'sisa_stok')) {
                    $table->integer('sisa_stok')->default(0)->after('stok_awal');
                }
                if (! Schema::hasColumn('tbl_barang', 'limit_minimum_stok')) {
                    $table->integer('limit_minimum_stok')->default(0)->after('sisa_stok');
                }
                if (! Schema::hasColumn('tbl_barang', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (! Schema::hasColumn('tbl_barang', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }

        if (! Schema::hasTable('stok_barang')) {
            Schema::create('stok_barang', function (Blueprint $table): void {
                $table->string('kode_barang')->primary();
                $table->string('nama_barang');
                $table->integer('stok_aktif')->default(0);
                $table->timestamps();

                $table->foreign('kode_barang')
                    ->references('kode_barang')
                    ->on('tbl_barang')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
            });
        } else {
            Schema::table('stok_barang', function (Blueprint $table): void {
                if (! Schema::hasColumn('stok_barang', 'kode_barang')) {
                    $table->string('kode_barang')->primary();
                }
                if (! Schema::hasColumn('stok_barang', 'nama_barang')) {
                    $table->string('nama_barang')->after('kode_barang');
                }
                if (! Schema::hasColumn('stok_barang', 'stok_aktif')) {
                    $table->integer('stok_aktif')->default(0)->after('nama_barang');
                }
                if (! Schema::hasColumn('stok_barang', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (! Schema::hasColumn('stok_barang', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_barang');
        Schema::dropIfExists('tbl_barang');
    }
};
