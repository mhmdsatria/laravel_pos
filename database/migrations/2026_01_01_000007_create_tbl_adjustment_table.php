<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_adjustment')) {
            Schema::create('tbl_adjustment', function (Blueprint $table): void {
                $table->id();
                $table->dateTime('tgl_adjustment');
                $table->string('kode_barang');
                $table->integer('stok_sistem')->default(0);
                $table->integer('stok_fisik')->default(0);
                $table->integer('selisih')->default(0);
                $table->text('keterangan');
                $table->string('staf_gudang');
                $table->string('diverifikasi_oleh')->nullable();
                $table->enum('status', ['Pending', 'Synced'])->default('Pending');
                $table->timestamps();
                $table->index('kode_barang');
                $table->index('status');
                $table->index('tgl_adjustment');
            });
        } else {
            Schema::table('tbl_adjustment', function (Blueprint $table): void {
                if (! Schema::hasColumn('tbl_adjustment', 'tgl_adjustment')) {
                    $table->dateTime('tgl_adjustment')->nullable()->after('id');
                }
                if (! Schema::hasColumn('tbl_adjustment', 'kode_barang')) {
                    $table->string('kode_barang')->nullable()->after('tgl_adjustment');
                }
                if (! Schema::hasColumn('tbl_adjustment', 'stok_sistem')) {
                    $table->integer('stok_sistem')->default(0)->after('kode_barang');
                }
                if (! Schema::hasColumn('tbl_adjustment', 'stok_fisik')) {
                    $table->integer('stok_fisik')->default(0)->after('stok_sistem');
                }
                if (! Schema::hasColumn('tbl_adjustment', 'selisih')) {
                    $table->integer('selisih')->default(0)->after('stok_fisik');
                }
                if (! Schema::hasColumn('tbl_adjustment', 'keterangan')) {
                    $table->text('keterangan')->nullable()->after('selisih');
                }
                if (! Schema::hasColumn('tbl_adjustment', 'staf_gudang')) {
                    $table->string('staf_gudang')->nullable()->after('keterangan');
                }
                if (! Schema::hasColumn('tbl_adjustment', 'diverifikasi_oleh')) {
                    $table->string('diverifikasi_oleh')->nullable()->after('staf_gudang');
                }
                if (! Schema::hasColumn('tbl_adjustment', 'status')) {
                    $table->enum('status', ['Pending', 'Synced'])->default('Pending')->after('diverifikasi_oleh');
                }
                if (! Schema::hasColumn('tbl_adjustment', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (! Schema::hasColumn('tbl_adjustment', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_adjustment');
    }
};
