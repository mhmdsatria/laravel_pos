<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stok_barang')) {
            Schema::create('stok_barang', function (Blueprint $table) {
                $table->string('kode_barang')->primary();
                $table->string('nama_barang');
                $table->integer('stok_aktif')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('stok_barang')) {
            Schema::table('stok_barang', function (Blueprint $table) {
                if (! Schema::hasColumn('stok_barang', 'nama_barang')) {
                    $table->string('nama_barang')->default('Produk Tanpa Nama');
                }

                if (! Schema::hasColumn('stok_barang', 'stok_aktif')) {
                    $table->integer('stok_aktif')->default(0);
                }

                if (! Schema::hasColumn('stok_barang', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }

                if (! Schema::hasColumn('stok_barang', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('tbl_barang') && Schema::hasTable('stok_barang')) {
            $products = DB::table('tbl_barang')->get();

            foreach ($products as $product) {
                $payload = [
                    'kode_barang' => $product->kode_barang,
                    'nama_barang' => $product->nama_barang,
                    'stok_aktif' => (int) ($product->sisa_stok ?? 0),
                ];

                if (Schema::hasColumn('stok_barang', 'created_at')) {
                    $payload['created_at'] = now();
                }

                if (Schema::hasColumn('stok_barang', 'updated_at')) {
                    $payload['updated_at'] = now();
                }

                DB::table('stok_barang')->updateOrInsert(
                    ['kode_barang' => $product->kode_barang],
                    $payload
                );
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stok_barang')) {
            Schema::table('stok_barang', function (Blueprint $table) {
                if (Schema::hasColumn('stok_barang', 'created_at')) {
                    $table->dropColumn('created_at');
                }

                if (Schema::hasColumn('stok_barang', 'updated_at')) {
                    $table->dropColumn('updated_at');
                }
            });
        }
    }
};
