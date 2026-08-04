<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tbl_penjualan_detail', 'qty_terkirim')) {
            Schema::table('tbl_penjualan_detail', function (Blueprint $table): void {
                $table->unsignedInteger('qty_terkirim')
                    ->default(0)
                    ->after('qty');
            });
        }

        /*
         * Opsional: migrasikan data pengiriman lama jika tabel surat jalan masih ada.
         * Setiap detail penjualan akan diisi berdasarkan total qty_kirim historis.
         */
        if (
            Schema::hasTable('tbl_surat_jalan')
            && Schema::hasTable('tbl_surat_jalan_detail')
        ) {
            $sentRows = DB::table('tbl_surat_jalan as sj')
                ->join(
                    'tbl_surat_jalan_detail as sd',
                    'sd.surat_jalan_id',
                    '=',
                    'sj.id'
                )
                ->selectRaw(
                    'sj.penjualan_id, sd.kode_barang, SUM(sd.qty_kirim) as total_terkirim'
                )
                ->groupBy('sj.penjualan_id', 'sd.kode_barang')
                ->get();

            foreach ($sentRows as $sentRow) {
                $detail = DB::table('tbl_penjualan_detail')
                    ->where('penjualan_id', $sentRow->penjualan_id)
                    ->where('kode_barang', $sentRow->kode_barang)
                    ->orderBy('id')
                    ->first();

                if (! $detail) {
                    continue;
                }

                DB::table('tbl_penjualan_detail')
                    ->where('id', $detail->id)
                    ->update([
                        'qty_terkirim' => min(
                            (int) $detail->qty,
                            max(0, (int) $sentRow->total_terkirim)
                        ),
                    ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tbl_penjualan_detail', 'qty_terkirim')) {
            Schema::table('tbl_penjualan_detail', function (Blueprint $table): void {
                $table->dropColumn('qty_terkirim');
            });
        }
    }
};
