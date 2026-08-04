<?php

namespace App\Providers;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Window;
use Throwable;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Dijalankan setiap aplikasi NativePHP dibuka.
     */
    public function boot(): void
    {
        $this->seedInitialDatabase();

        Window::open()
            ->title('Toko Bangunan')
            ->width(1280)
            ->height(800)
            ->minWidth(1024)
            ->minHeight(650);
    }

    /**
     * Membuat akun admin hanya jika belum tersedia.
     */
    private function seedInitialDatabase(): void
    {
        try {
            $adminExists = User::query()
                ->where('username', 'admin')
                ->exists();

            if ($adminExists) {
                return;
            }

            Artisan::call('db:seed', [
                '--class' => UserSeeder::class,
                '--force' => true,
            ]);

            Log::info('Database awal berhasil di-seed.', [
                'output' => Artisan::output(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Database awal gagal di-seed.', [
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * Konfigurasi PHP bawaan NativePHP.
     */
    public function phpIni(): array
    {
        return [
            'memory_limit' => '512M',
            'max_execution_time' => '300',
        ];
    }
}