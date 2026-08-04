<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_penjualan') && ! Schema::hasColumn('tbl_penjualan', 'sisa_piutang')) {
            Schema::table('tbl_penjualan', function (Blueprint $table): void {
                $table->unsignedBigInteger('sisa_piutang')->default(0)->after('total_belanja');
            });
        }

        if (! Schema::hasTable('tbl_piutang_cicilan')) {
            Schema::create('tbl_piutang_cicilan', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('penjualan_id');
                $table->date('tgl_bayar');
                $table->unsignedBigInteger('nominal')->default(0);
                $table->string('penerima_kasir');
                $table->timestamps();

                $table->index('penjualan_id');
                $table->index('tgl_bayar');
            });
        }

        if (Schema::hasTable('tbl_penjualan') && Schema::hasColumn('tbl_penjualan', 'sisa_piutang')) {
            DB::table('tbl_penjualan')
                ->where('metode_bayar', 'TEMPO')
                ->where(function ($query): void {
                    $query->whereNull('sisa_piutang')->orWhere('sisa_piutang', 0);
                })
                ->update(['sisa_piutang' => DB::raw('total_belanja')]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbl_piutang_cicilan')) {
            Schema::dropIfExists('tbl_piutang_cicilan');
        }

        if (Schema::hasTable('tbl_penjualan') && Schema::hasColumn('tbl_penjualan', 'sisa_piutang')) {
            Schema::table('tbl_penjualan', function (Blueprint $table): void {
                $table->dropColumn('sisa_piutang');
            });
        }
    }
};
