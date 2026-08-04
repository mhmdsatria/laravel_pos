<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_pengaturan')) {
            Schema::create('tbl_pengaturan', function (Blueprint $table): void {
                $table->id();
                $table->string('kunci_pengaturan')->unique();
                $table->text('nilai_pengaturan')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('tbl_pengaturan', function (Blueprint $table): void {
            if (! Schema::hasColumn('tbl_pengaturan', 'kunci_pengaturan')) {
                $table->string('kunci_pengaturan')->unique()->after('id');
            }
            if (! Schema::hasColumn('tbl_pengaturan', 'nilai_pengaturan')) {
                $table->text('nilai_pengaturan')->nullable()->after('kunci_pengaturan');
            }
            if (! Schema::hasColumn('tbl_pengaturan', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (! Schema::hasColumn('tbl_pengaturan', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_pengaturan');
    }
};
