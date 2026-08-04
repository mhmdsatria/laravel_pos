<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. tbl_barang
        if (Schema::hasTable('tbl_barang')) {
            Schema::table('tbl_barang', function (Blueprint $table): void {
                if (Schema::hasColumn('tbl_barang', 'stok_awal')) {
                    $table->decimal('stok_awal', 12, 3)->default(0)->change();
                }
                if (Schema::hasColumn('tbl_barang', 'sisa_stok')) {
                    $table->decimal('sisa_stok', 12, 3)->default(0)->change();
                }
                if (Schema::hasColumn('tbl_barang', 'limit_minimum_stok')) {
                    $table->decimal('limit_minimum_stok', 12, 3)->default(0)->change();
                }
            });
        }

        // 2. stok_barang
        if (Schema::hasTable('stok_barang')) {
            Schema::table('stok_barang', function (Blueprint $table): void {
                if (Schema::hasColumn('stok_barang', 'stok_aktif')) {
                    $table->decimal('stok_aktif', 12, 3)->default(0)->change();
                }
            });
        }

        // 3. tbl_penjualan_detail
        if (Schema::hasTable('tbl_penjualan_detail')) {
            Schema::table('tbl_penjualan_detail', function (Blueprint $table): void {
                if (Schema::hasColumn('tbl_penjualan_detail', 'qty')) {
                    $table->decimal('qty', 12, 3)->default(0)->change();
                }
                if (Schema::hasColumn('tbl_penjualan_detail', 'qty_terkirim')) {
                    $table->decimal('qty_terkirim', 12, 3)->default(0)->change();
                }
                if (Schema::hasColumn('tbl_penjualan_detail', 'qty_refund')) {
                    $table->decimal('qty_refund', 12, 3)->default(0)->change();
                }
            });
        }

        // 4. tbl_pembelian_detail
        if (Schema::hasTable('tbl_pembelian_detail')) {
            Schema::table('tbl_pembelian_detail', function (Blueprint $table): void {
                if (Schema::hasColumn('tbl_pembelian_detail', 'qty')) {
                    $table->decimal('qty', 12, 3)->default(0)->change();
                }
            });
        }

        // 5. tbl_surat_jalan_detail
        if (Schema::hasTable('tbl_surat_jalan_detail')) {
            Schema::table('tbl_surat_jalan_detail', function (Blueprint $table): void {
                if (Schema::hasColumn('tbl_surat_jalan_detail', 'qty_kirim')) {
                    $table->decimal('qty_kirim', 12, 3)->default(0)->change();
                }
            });
        }

        // 6. tbl_penjualan_refund_detail
        if (Schema::hasTable('tbl_penjualan_refund_detail')) {
            Schema::table('tbl_penjualan_refund_detail', function (Blueprint $table): void {
                if (Schema::hasColumn('tbl_penjualan_refund_detail', 'qty_refund')) {
                    $table->decimal('qty_refund', 12, 3)->default(0)->change();
                }
            });
        }

        // 7. tbl_adjustment
        if (Schema::hasTable('tbl_adjustment')) {
            Schema::table('tbl_adjustment', function (Blueprint $table): void {
                if (Schema::hasColumn('tbl_adjustment', 'stok_sistem')) {
                    $table->decimal('stok_sistem', 12, 3)->default(0)->change();
                }
                if (Schema::hasColumn('tbl_adjustment', 'stok_fisik')) {
                    $table->decimal('stok_fisik', 12, 3)->default(0)->change();
                }
                if (Schema::hasColumn('tbl_adjustment', 'selisih')) {
                    $table->decimal('selisih', 12, 3)->default(0)->change();
                }
            });
        }
    }

    public function down(): void
    {
    }
};
