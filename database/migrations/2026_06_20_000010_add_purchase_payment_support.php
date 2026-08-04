<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_pembelian')) {
            Schema::table('tbl_pembelian', function (Blueprint $table): void {
                if (! Schema::hasColumn('tbl_pembelian', 'note')) {
                    $table->text('note')->nullable()->after('status');
                }
                if (! Schema::hasColumn('tbl_pembelian', 'paid_total')) {
                    $table->bigInteger('paid_total')->default(0)->after('total_harga');
                }
                if (! Schema::hasColumn('tbl_pembelian', 'remaining_total')) {
                    $table->bigInteger('remaining_total')->default(0)->after('paid_total');
                }
                if (! Schema::hasColumn('tbl_pembelian', 'payment_status')) {
                    $table->string('payment_status')->default('Belum Dibayar')->after('remaining_total')->index();
                }
            });
        }

        if (! Schema::hasTable('tbl_pembelian_payment')) {
            Schema::create('tbl_pembelian_payment', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('pembelian_id');
                $table->date('payment_date');
                $table->bigInteger('amount')->default(0);
                $table->string('payment_method')->nullable();
                $table->text('payment_note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index('pembelian_id');
                $table->index('payment_date');
                $table->index('created_by');
            });
        }

        if (Schema::hasTable('tbl_pembelian') && Schema::hasTable('tbl_pembelian_payment')) {
            $purchases = DB::table('tbl_pembelian')->select('id', 'total_harga')->get();
            foreach ($purchases as $purchase) {
                $paymentCount = DB::table('tbl_pembelian_payment')->where('pembelian_id', $purchase->id)->count();
                if ($paymentCount === 0 && (int) ($purchase->total_harga ?? 0) > 0) {
                    DB::table('tbl_pembelian_payment')->insert([
                        'pembelian_id' => $purchase->id,
                        'payment_date' => now()->toDateString(),
                        'amount' => (int) $purchase->total_harga,
                        'payment_method' => 'Migrasi',
                        'payment_note' => 'Saldo awal dari data pembelian lama sebelum fitur pembayaran.',
                        'created_by' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $paidTotal = (int) DB::table('tbl_pembelian_payment')->where('pembelian_id', $purchase->id)->sum('amount');
                $totalHarga = (int) ($purchase->total_harga ?? 0);
                $remaining = max(0, $totalHarga - $paidTotal);
                $status = $totalHarga <= 0 || $paidTotal >= $totalHarga
                    ? 'Lunas'
                    : ($paidTotal > 0 ? 'Dibayar Sebagian' : 'Belum Dibayar');

                DB::table('tbl_pembelian')->where('id', $purchase->id)->update([
                    'paid_total' => $paidTotal,
                    'remaining_total' => $remaining,
                    'payment_status' => $status,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_pembelian_payment');

        if (Schema::hasTable('tbl_pembelian')) {
            Schema::table('tbl_pembelian', function (Blueprint $table): void {
                if (Schema::hasColumn('tbl_pembelian', 'payment_status')) {
                    $table->dropColumn('payment_status');
                }
                if (Schema::hasColumn('tbl_pembelian', 'remaining_total')) {
                    $table->dropColumn('remaining_total');
                }
                if (Schema::hasColumn('tbl_pembelian', 'paid_total')) {
                    $table->dropColumn('paid_total');
                }
                if (Schema::hasColumn('tbl_pembelian', 'note')) {
                    $table->dropColumn('note');
                }
            });
        }
    }
};
