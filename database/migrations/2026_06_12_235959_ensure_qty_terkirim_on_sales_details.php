<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_penjualan_detail')) {
            return;
        }

        if (! Schema::hasColumn('tbl_penjualan_detail', 'qty_terkirim')) {
            Schema::table('tbl_penjualan_detail', function (Blueprint $table): void {
                $table->unsignedInteger('qty_terkirim')->default(0)->after('qty');
            });
        }

        DB::table('tbl_penjualan_detail')
            ->whereNull('qty_terkirim')
            ->update(['qty_terkirim' => 0]);

        if (
            Schema::hasTable('tbl_surat_jalan')
            && Schema::hasTable('tbl_surat_jalan_detail')
        ) {
            $legacyRows = DB::table('tbl_surat_jalan as sj')
                ->join('tbl_surat_jalan_detail as sd', 'sd.surat_jalan_id', '=', 'sj.id')
                ->selectRaw('sj.penjualan_id, sd.kode_barang, SUM(sd.qty_kirim) as total_terkirim')
                ->groupBy('sj.penjualan_id', 'sd.kode_barang')
                ->get();

            foreach ($legacyRows as $legacyRow) {
                $details = DB::table('tbl_penjualan_detail')
                    ->where('penjualan_id', $legacyRow->penjualan_id)
                    ->where('kode_barang', $legacyRow->kode_barang)
                    ->orderBy('id')
                    ->get(['id', 'qty', 'qty_terkirim']);

                $remaining = max(0, (int) $legacyRow->total_terkirim);

                foreach ($details as $detail) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $assigned = min((int) $detail->qty, $remaining);

                    DB::table('tbl_penjualan_detail')
                        ->where('id', $detail->id)
                        ->update([
                            'qty_terkirim' => max(
                                (int) ($detail->qty_terkirim ?? 0),
                                $assigned
                            ),
                        ]);

                    $remaining -= $assigned;
                }
            }
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('tbl_penjualan_detail')
            && Schema::hasColumn('tbl_penjualan_detail', 'qty_terkirim')
        ) {
            Schema::table('tbl_penjualan_detail', function (Blueprint $table): void {
                $table->dropColumn('qty_terkirim');
            });
        }
    }
};
