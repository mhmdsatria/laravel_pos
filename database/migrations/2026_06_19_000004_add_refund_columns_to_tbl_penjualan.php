<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_penjualan')) {
            return;
        }

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
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tbl_penjualan')) {
            return;
        }

        Schema::table('tbl_penjualan', function (Blueprint $table): void {
            foreach (['refunded_by', 'refund_reason', 'refund_at'] as $column) {
                if (Schema::hasColumn('tbl_penjualan', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
