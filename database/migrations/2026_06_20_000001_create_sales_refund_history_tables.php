<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_penjualan_refund')) {
            Schema::create('tbl_penjualan_refund', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('penjualan_id');
                $table->string('no_refund')->unique();
                $table->dateTime('refund_at')->nullable();
                $table->unsignedBigInteger('refund_total')->default(0);
                $table->text('refund_reason')->nullable();
                $table->string('refunded_by')->nullable();
                $table->timestamps();

                $table->index('penjualan_id');
                $table->index('refund_at');
            });
        }

        if (! Schema::hasTable('tbl_penjualan_refund_detail')) {
            Schema::create('tbl_penjualan_refund_detail', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('refund_id');
                $table->unsignedBigInteger('penjualan_detail_id')->nullable();
                $table->string('kode_barang');
                $table->unsignedInteger('qty_refund')->default(0);
                $table->unsignedBigInteger('harga_jual')->default(0);
                $table->unsignedBigInteger('subtotal_refund')->default(0);
                $table->timestamps();

                $table->index('refund_id');
                $table->index('penjualan_detail_id');
                $table->index('kode_barang');
            });
        }

        $this->backfillExistingRefunds();
    }

    private function backfillExistingRefunds(): void
    {
        if (
            ! Schema::hasTable('tbl_penjualan')
            || ! Schema::hasTable('tbl_penjualan_detail')
            || ! Schema::hasColumn('tbl_penjualan', 'refund_total')
            || ! Schema::hasColumn('tbl_penjualan_detail', 'qty_refund')
        ) {
            return;
        }

        $orders = DB::table('tbl_penjualan')
            ->where('refund_total', '>', 0)
            ->orderBy('id')
            ->get();

        foreach ($orders as $order) {
            $existing = DB::table('tbl_penjualan_refund')
                ->where('penjualan_id', $order->id)
                ->exists();

            if ($existing) {
                continue;
            }

            $details = DB::table('tbl_penjualan_detail')
                ->where('penjualan_id', $order->id)
                ->where('qty_refund', '>', 0)
                ->orderBy('id')
                ->get();

            if ($details->isEmpty()) {
                continue;
            }

            $total = (int) $details->sum(function ($detail): int {
                return (int) $detail->qty_refund * (int) $detail->harga_jual;
            });

            $createdAt = $order->refund_at ?? now();
            $refundId = DB::table('tbl_penjualan_refund')->insertGetId([
                'penjualan_id' => $order->id,
                'no_refund' => 'RFN/LEGACY/'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
                'refund_at' => $order->refund_at ?? $order->updated_at ?? now(),
                'refund_total' => $total > 0 ? $total : (int) $order->refund_total,
                'refund_reason' => $order->refund_reason ?? 'Migrasi histori refund lama',
                'refunded_by' => $order->refunded_by ?? null,
                'created_at' => $createdAt,
                'updated_at' => $order->updated_at ?? now(),
            ]);

            foreach ($details as $detail) {
                DB::table('tbl_penjualan_refund_detail')->insert([
                    'refund_id' => $refundId,
                    'penjualan_detail_id' => $detail->id,
                    'kode_barang' => $detail->kode_barang,
                    'qty_refund' => (int) $detail->qty_refund,
                    'harga_jual' => (int) $detail->harga_jual,
                    'subtotal_refund' => (int) $detail->qty_refund * (int) $detail->harga_jual,
                    'created_at' => $createdAt,
                    'updated_at' => $order->updated_at ?? now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_penjualan_refund_detail');
        Schema::dropIfExists('tbl_penjualan_refund');
    }
};
