<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_penjualan')) {
            Schema::table('tbl_penjualan', function (Blueprint $table): void {
                if (! Schema::hasColumn('tbl_penjualan', 'refund_at')) {
                    $table->timestamp('refund_at')->nullable()->after('status');
                }

                if (! Schema::hasColumn('tbl_penjualan', 'refund_reason')) {
                    $table->text('refund_reason')->nullable()->after('refund_at');
                }

                if (! Schema::hasColumn('tbl_penjualan', 'refunded_by')) {
                    $table->string('refunded_by')->nullable()->after('refund_reason');
                }

                if (! Schema::hasColumn('tbl_penjualan', 'refund_total')) {
                    $table->unsignedBigInteger('refund_total')->default(0)->after('total_belanja');
                }
            });
        }

        if (Schema::hasTable('tbl_penjualan_detail')) {
            Schema::table('tbl_penjualan_detail', function (Blueprint $table): void {
                if (! Schema::hasColumn('tbl_penjualan_detail', 'qty_refund')) {
                    $table->unsignedInteger('qty_refund')->default(0)->after('qty_terkirim');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbl_penjualan_detail')) {
            Schema::table('tbl_penjualan_detail', function (Blueprint $table): void {
                if (Schema::hasColumn('tbl_penjualan_detail', 'qty_refund')) {
                    $table->dropColumn('qty_refund');
                }
            });
        }

        if (Schema::hasTable('tbl_penjualan')) {
            Schema::table('tbl_penjualan', function (Blueprint $table): void {
                foreach (['refund_total', 'refunded_by', 'refund_reason', 'refund_at'] as $column) {
                    if (Schema::hasColumn('tbl_penjualan', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
