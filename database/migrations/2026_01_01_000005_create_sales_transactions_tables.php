<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_penjualan')) {
            Schema::create('tbl_penjualan', function (Blueprint $table) {
                $table->id();
                $table->string('no_invoice')->unique();
                $table->dateTime('tgl_transaksi');
                $table->enum('tipe_pelanggan', ['USER', 'TOKO', 'SALES'])->default('USER');
                $table->string('nama_pelanggan');
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('sales_id')->nullable();
                $table->unsignedBigInteger('total_belanja')->default(0);
                $table->enum('metode_bayar', ['CASH', 'TEMPO'])->default('CASH');
                $table->enum('status', ['Lunas', 'Tempo'])->default('Lunas');
                $table->date('jatuh_tempo')->nullable();
                $table->string('cashier_name');
                $table->timestamps();

                $table->index('tgl_transaksi');
                $table->index('tipe_pelanggan');
                $table->index('customer_id');
                $table->index('sales_id');
                $table->index('status');
            });
        } else {
            Schema::table('tbl_penjualan', function (Blueprint $table) {
                if (! Schema::hasColumn('tbl_penjualan', 'no_invoice')) {
                    $table->string('no_invoice')->unique()->after('id');
                }
                if (! Schema::hasColumn('tbl_penjualan', 'tgl_transaksi')) {
                    $table->dateTime('tgl_transaksi')->after('no_invoice');
                }
                if (! Schema::hasColumn('tbl_penjualan', 'tipe_pelanggan')) {
                    $table->enum('tipe_pelanggan', ['USER', 'TOKO', 'SALES'])->default('USER')->after('tgl_transaksi');
                }
                if (! Schema::hasColumn('tbl_penjualan', 'nama_pelanggan')) {
                    $table->string('nama_pelanggan')->after('tipe_pelanggan');
                }
                if (! Schema::hasColumn('tbl_penjualan', 'customer_id')) {
                    $table->unsignedBigInteger('customer_id')->nullable()->after('nama_pelanggan');
                }
                if (! Schema::hasColumn('tbl_penjualan', 'sales_id')) {
                    $table->unsignedBigInteger('sales_id')->nullable()->after('customer_id');
                }
                if (! Schema::hasColumn('tbl_penjualan', 'total_belanja')) {
                    $table->unsignedBigInteger('total_belanja')->default(0)->after('sales_id');
                }
                if (! Schema::hasColumn('tbl_penjualan', 'metode_bayar')) {
                    $table->enum('metode_bayar', ['CASH', 'TEMPO'])->default('CASH')->after('total_belanja');
                }
                if (! Schema::hasColumn('tbl_penjualan', 'status')) {
                    $table->enum('status', ['Lunas', 'Tempo'])->default('Lunas')->after('metode_bayar');
                }
                if (! Schema::hasColumn('tbl_penjualan', 'jatuh_tempo')) {
                    $table->date('jatuh_tempo')->nullable()->after('status');
                }
                if (! Schema::hasColumn('tbl_penjualan', 'cashier_name')) {
                    $table->string('cashier_name')->after('jatuh_tempo');
                }
                if (! Schema::hasColumn('tbl_penjualan', 'created_at')) {
                    $table->timestamps();
                }
            });
        }

        if (! Schema::hasTable('tbl_penjualan_detail')) {
            Schema::create('tbl_penjualan_detail', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('penjualan_id');
                $table->string('kode_barang');
                $table->unsignedInteger('qty');
                $table->unsignedInteger('qty_terkirim')->default(0);
                $table->unsignedBigInteger('harga_jual')->default(0);
                $table->unsignedBigInteger('subtotal')->default(0);
                $table->timestamps();

                $table->index('penjualan_id');
                $table->index('kode_barang');
            });
        } else {
            Schema::table('tbl_penjualan_detail', function (Blueprint $table) {
                if (! Schema::hasColumn('tbl_penjualan_detail', 'penjualan_id')) {
                    $table->unsignedBigInteger('penjualan_id')->after('id');
                }
                if (! Schema::hasColumn('tbl_penjualan_detail', 'kode_barang')) {
                    $table->string('kode_barang')->after('penjualan_id');
                }
                if (! Schema::hasColumn('tbl_penjualan_detail', 'qty')) {
                    $table->unsignedInteger('qty')->after('kode_barang');
                }
                if (! Schema::hasColumn('tbl_penjualan_detail', 'qty_terkirim')) {
                    $table->unsignedInteger('qty_terkirim')->default(0)->after('qty');
                }
                if (! Schema::hasColumn('tbl_penjualan_detail', 'harga_jual')) {
                    $table->unsignedBigInteger('harga_jual')->default(0)->after('qty');
                }
                if (! Schema::hasColumn('tbl_penjualan_detail', 'subtotal')) {
                    $table->unsignedBigInteger('subtotal')->default(0)->after('harga_jual');
                }
                if (! Schema::hasColumn('tbl_penjualan_detail', 'created_at')) {
                    $table->timestamps();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_penjualan_detail');
        Schema::dropIfExists('tbl_penjualan');
    }
};
