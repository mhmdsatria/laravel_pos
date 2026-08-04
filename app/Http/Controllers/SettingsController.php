<?php

namespace App\Http\Controllers;

use App\Services\DesktopBridge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class SettingsController extends Controller
{
    public function index(DesktopBridge $desktop): View
    {
        $settings = $this->settingsMap();

        return view('pages.settings', [
            'availablePrinters' => $desktop->printers(),
            'nativeAvailable' => $desktop->available(),
            'printerKasir' => $settings['printer_kasir'] ?? '',
            'printerKantor' => $settings['printer_kantor'] ?? '',
            'dataDirectory' => $settings['data_directory'] ?? '',
            'lastBackupTime' => $settings['last_backup_time'] ?? '-',
            'lastBackupSize' => $settings['last_backup_size'] ?? '-',
            'lastBackupPath' => $settings['last_backup_path'] ?? '-',
            'lastRestoreTime' => $settings['last_restore_time'] ?? '-',
            'lastRestorePath' => $settings['last_restore_path'] ?? '-',
        ]);
    }

    public function savePrinterMapping(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'printer_kasir' => ['nullable', 'string', 'max:255'],
            'printer_kantor' => ['nullable', 'string', 'max:255'],
        ]);

        $this->saveSetting('printer_kasir', $validated['printer_kasir'] ?? null);
        $this->saveSetting('printer_kantor', $validated['printer_kantor'] ?? null);

        return back()->with('success', 'Pengaturan printer berhasil disimpan.');
    }

    public function chooseDataDirectory(DesktopBridge $desktop): RedirectResponse
    {
        $current = $this->settingsMap()['data_directory'] ?? null;
        $folder = $desktop->selectFolder($current);

        if (! $folder) {
            return back()->withErrors([
                'data_directory' => 'Pemilihan folder dibatalkan atau dialog native belum tersedia.',
            ]);
        }

        $folder = $this->normalizePath($folder);

        if (! is_dir($folder) && ! mkdir($folder, 0775, true) && ! is_dir($folder)) {
            return back()->withErrors(['data_directory' => 'Folder tidak dapat dibuat.']);
        }

        if (! is_writable($folder)) {
            return back()->withErrors(['data_directory' => 'Folder yang dipilih tidak memiliki izin tulis.']);
        }

        $this->saveSetting('data_directory', $folder);

        return back()->with('success', 'Folder data dan cadangan berhasil disimpan.');
    }

    public function testPrinter(Request $request, DesktopBridge $desktop): RedirectResponse
    {
        $validated = $request->validate([
            'printer_type' => ['required', 'in:kasir,kantor'],
        ]);

        $settings = $this->settingsMap();
        $isReceipt = $validated['printer_type'] === 'kasir';
        $printerName = $settings[$isReceipt ? 'printer_kasir' : 'printer_kantor'] ?? null;

        $html = view('prints.printer-test', [
            'title' => $isReceipt ? 'TEST PRINTER KASIR' : 'TEST PRINTER KANTOR',
            'paper' => $isReceipt ? 'receipt' : 'a4',
            'printerName' => $printerName ?: 'Printer Default',
        ])->render();

        $printed = $desktop->printHtml(
            $html,
            $printerName,
            $isReceipt
                ? ['pageSize' => ['width' => 80000, 'height' => 75000], 'landscape' => false]
                : ['pageSize' => ['width' => 210000, 'height' => 90000], 'landscape' => false]
        );

        if (! $printed) {
            return back()->withErrors([
                'printer' => 'NativePHP belum aktif atau printer tidak dapat diakses.',
            ]);
        }

        return back()->with('success', 'Halaman uji berhasil dikirim ke printer.');
    }

    public function triggerBackup(Request $request, DesktopBridge $desktop): RedirectResponse
    {
        set_time_limit(0);

        try {
            $settings = $this->settingsMap();
            $folder = trim((string) $request->input('backup_path', ''))
                ?: ($settings['data_directory'] ?? null)
                ?: $desktop->selectFolder();

            if (! $folder) {
                return back()->withErrors(['backup' => 'Pilih folder data terlebih dahulu.']);
            }

            $folder = $this->normalizePath($folder);
            if (! is_dir($folder) && ! mkdir($folder, 0775, true) && ! is_dir($folder)) {
                throw new RuntimeException('Folder backup tidak dapat dibuat.');
            }

            $driver = $this->databaseDriver();
            $extension = $driver === 'sqlite' ? 'sqlite' : 'sql';
            $fileName = 'tb39_backup_' . now()->format('Ymd_His') . '.' . $extension;
            $filePath = rtrim($folder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

            if ($driver === 'sqlite') {
                $this->backupSqlite($filePath);
            } else {
                $this->backupMysql($filePath);
            }

            if (! file_exists($filePath)) {
                throw new RuntimeException('File backup tidak berhasil dibuat.');
            }

            $size = $this->humanFileSize((int) (filesize($filePath) ?: 0));
            $time = now()->format('d M Y H:i:s');

            $this->saveSetting('last_backup_time', $time);
            $this->saveSetting('last_backup_size', $size);
            $this->saveSetting('last_backup_path', $filePath);

            return back()->with('success', "Backup berhasil dibuat: {$filePath}");
        } catch (Throwable $exception) {
            Log::error('Backup database gagal.', ['message' => $exception->getMessage()]);
            return back()->withErrors(['backup' => 'Backup gagal: ' . $exception->getMessage()]);
        }
    }

    public function triggerRestore(Request $request, DesktopBridge $desktop): RedirectResponse
    {
        set_time_limit(0);

        try {
            $settings = $this->settingsMap();
            $filePath = trim((string) $request->input('restore_path', ''))
                ?: $desktop->selectBackupFile($settings['data_directory'] ?? null);

            if (! $filePath || ! file_exists($filePath)) {
                return back()->withErrors(['restore' => 'File cadangan tidak dipilih atau tidak ditemukan.']);
            }

            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $driver = $this->databaseDriver();

            if ($driver === 'sqlite') {
                if (! in_array($extension, ['sqlite', 'db'], true)) {
                    throw new RuntimeException('SQLite hanya dapat dipulihkan dari file .sqlite atau .db.');
                }
                $this->restoreSqlite($filePath);
            } else {
                if ($extension !== 'sql') {
                    throw new RuntimeException('MySQL hanya dapat dipulihkan dari file .sql.');
                }
                $this->restoreMysql($filePath);
            }

            $time = now()->format('d M Y H:i:s');
            $this->saveSetting('last_restore_time', $time);
            $this->saveSetting('last_restore_path', $filePath);

            return back()->with('success', 'Restore berhasil. Tutup dan buka kembali aplikasi.');
        } catch (Throwable $exception) {
            Log::error('Restore database gagal.', ['message' => $exception->getMessage()]);
            return back()->withErrors(['restore' => 'Restore gagal: ' . $exception->getMessage()]);
        }
    }

    private function databaseDriver(): string
    {
        try {
            return (string) DB::connection()->getDriverName();
        } catch (Throwable) {
            $connection = (string) config('database.default');
            return (string) config('database.connections.' . $connection . '.driver', $connection);
        }
    }

    private function sqliteDatabasePath(): string
    {
        try {
            $database = (string) DB::connection()->getDatabaseName();
        } catch (Throwable) {
            $connection = (string) config('database.default');
            $database = (string) config('database.connections.' . $connection . '.database', '');
        }

        if ($database === '' || $database === ':memory:') {
            throw new RuntimeException('Path database SQLite aktif tidak valid.');
        }

        return $this->resolveDatabasePath($database);
    }

    private function resolveDatabasePath(string $path): string
    {
        $path = trim($path);

        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        if (str_contains($path, '/') || str_contains($path, chr(92))) {
            return base_path($path);
        }

        return database_path($path);
    }

    private function isAbsolutePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        return $path[0] === '/'
            || (strlen($path) >= 3 && ctype_alpha($path[0]) && $path[1] === ':' && in_array($path[2], ['/', chr(92)], true))
            || str_starts_with($path, chr(92) . chr(92));
    }

    private function backupSqlite(string $target): void
    {
        $database = $this->sqliteDatabasePath();

        if (! is_file($database)) {
            throw new RuntimeException('File database SQLite aktif tidak ditemukan: ' . $database);
        }

        $targetDirectory = dirname($target);
        if (! is_dir($targetDirectory) && ! mkdir($targetDirectory, 0775, true) && ! is_dir($targetDirectory)) {
            throw new RuntimeException('Folder tujuan backup tidak dapat dibuat.');
        }

        try {
            DB::connection()->statement('PRAGMA wal_checkpoint(FULL)');
        } catch (Throwable $exception) {
            Log::warning('SQLite WAL checkpoint gagal saat backup.', ['message' => $exception->getMessage()]);
        }

        clearstatcache(true, $database);

        if (! copy($database, $target)) {
            throw new RuntimeException('Database SQLite tidak dapat disalin.');
        }

        clearstatcache(true, $target);

        if (! is_file($target) || (int) filesize($target) <= 0) {
            throw new RuntimeException('File backup SQLite kosong atau gagal dibuat.');
        }
    }

    private function restoreSqlite(string $source): void
    {
        $database = $this->sqliteDatabasePath();

        if (! is_file($source)) {
            throw new RuntimeException('File backup SQLite tidak ditemukan.');
        }

        $databaseDirectory = dirname($database);
        if (! is_dir($databaseDirectory) && ! mkdir($databaseDirectory, 0775, true) && ! is_dir($databaseDirectory)) {
            throw new RuntimeException('Folder database SQLite tidak dapat dibuat.');
        }

        DB::disconnect();

        if (! copy($source, $database)) {
            DB::reconnect();
            throw new RuntimeException('File SQLite tidak dapat dipulihkan.');
        }

        @unlink($database . '-wal');
        @unlink($database . '-shm');

        DB::reconnect();
    }

    private function backupMysql(string $target): void
    {
        $database = $this->databaseName();
        $output = [];
        $exitCode = 1;

        try {
            exec($this->mysqlCommand(true, $database, $target), $output, $exitCode);
        } catch (Throwable) {
            $exitCode = 1;
        }

        if ($exitCode === 0 && file_exists($target) && (int) filesize($target) > 0) {
            return;
        }

        $this->backupMysqlWithPdo($target);
    }

    private function restoreMysql(string $source): void
    {
        $database = $this->databaseName();
        $output = [];
        $exitCode = 1;

        try {
            exec($this->mysqlCommand(false, $database, $source), $output, $exitCode);
        } catch (Throwable) {
            $exitCode = 1;
        }

        if ($exitCode === 0) {
            return;
        }

        $this->restoreMysqlWithPdo($source);
    }

    private function mysqlCommand(bool $dump, string $database, string $file): string
    {
        $connection = (string) config('database.default');
        $binary = $dump ? env('MYSQLDUMP_BINARY', 'mysqldump') : env('MYSQL_BINARY', 'mysql');

        $parts = [
            escapeshellcmd((string) $binary),
            '--host=' . escapeshellarg((string) config('database.connections.' . $connection . '.host', '127.0.0.1')),
            '--port=' . escapeshellarg((string) config('database.connections.' . $connection . '.port', '3306')),
            '--user=' . escapeshellarg((string) config('database.connections.' . $connection . '.username', 'root')),
        ];

        $password = (string) config('database.connections.' . $connection . '.password', '');
        if ($password !== '') {
            $parts[] = '--password=' . escapeshellarg($password);
        }

        if ($dump) {
            $parts[] = '--single-transaction';
        }

        $parts[] = escapeshellarg($database);

        return implode(' ', $parts) . ($dump ? ' > ' : ' < ') . escapeshellarg($file) . ' 2>&1';
    }

    private function databaseName(): string
    {
        $connection = (string) config('database.default');
        $database = (string) config('database.connections.' . $connection . '.database', '');

        if ($database === '') {
            try {
                $database = (string) DB::connection()->getDatabaseName();
            } catch (Throwable) {
                $database = '';
            }
        }

        if ($database === '') {
            throw new RuntimeException('Nama database tidak ditemukan.');
        }

        return $database;
    }

    private function backupMysqlWithPdo(string $target): void
    {
        $connection = DB::connection();
        $pdo = $connection->getPdo();
        $tables = $connection->select('SHOW TABLES');

        $handle = fopen($target, 'wb');
        if ($handle === false) {
            throw new RuntimeException('File backup SQL tidak dapat dibuat.');
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach ($tables as $tableRow) {
            $tableData = (array) $tableRow;
            $table = (string) reset($tableData);
            if ($table === '') {
                continue;
            }

            $quotedTable = $this->quoteIdentifier($table);
            $createRow = $connection->selectOne('SHOW CREATE TABLE ' . $quotedTable);
            $createData = array_values((array) $createRow);
            $createSql = (string) ($createData[1] ?? '');

            fwrite($handle, "DROP TABLE IF EXISTS {$quotedTable};\n");
            fwrite($handle, $createSql . ";\n\n");

            foreach ($connection->table($table)->cursor() as $row) {
                $rowData = (array) $row;
                $columns = array_map(fn (string $column): string => $this->quoteIdentifier($column), array_keys($rowData));
                $values = array_map(function ($value) use ($pdo): string {
                    if ($value === null) {
                        return 'NULL';
                    }

                    if (is_bool($value)) {
                        return $value ? '1' : '0';
                    }

                    return $pdo->quote((string) $value);
                }, array_values($rowData));

                fwrite(
                    $handle,
                    'INSERT INTO ' . $quotedTable . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n"
                );
            }

            fwrite($handle, "\n");
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);
    }

    private function restoreMysqlWithPdo(string $source): void
    {
        $sql = file_get_contents($source);
        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException('File SQL kosong atau tidak dapat dibaca.');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($this->splitSqlStatements($sql) as $statement) {
            $statement = trim($statement);
            if ($statement === '' || str_starts_with($statement, '--') || str_starts_with($statement, '/*')) {
                continue;
            }

            DB::unprepared($statement);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $quote = null;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $i + 1 < $length ? $sql[$i + 1] : '';

            if ($quote === null && $char === '-' && $next === '-') {
                while ($i < $length && $sql[$i] !== "\n") {
                    $i++;
                }
                continue;
            }

            if ($quote === null && $char === '/' && $next === '*') {
                $i += 2;
                while ($i < $length - 1 && ! ($sql[$i] === '*' && $sql[$i + 1] === '/')) {
                    $i++;
                }
                $i++;
                continue;
            }

            if (($char === "'" || $char === '"') && ($i === 0 || $sql[$i - 1] !== '\\')) {
                $quote = $quote === $char ? null : ($quote ?? $char);
            }

            if ($char === ';' && $quote === null) {
                $statements[] = $buffer;
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        if (trim($buffer) !== '') {
            $statements[] = $buffer;
        }

        return $statements;
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function settingsMap(): array
    {
        if (! Schema::hasTable('tbl_pengaturan')) {
            return [];
        }

        return DB::table('tbl_pengaturan')
            ->pluck('nilai_pengaturan', 'kunci_pengaturan')
            ->toArray();
    }

    private function saveSetting(string $key, ?string $value): void
    {
        if (! Schema::hasTable('tbl_pengaturan')) {
            return;
        }

        DB::table('tbl_pengaturan')->updateOrInsert(
            ['kunci_pengaturan' => $key],
            ['nilai_pengaturan' => $value, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    private function normalizePath(string $path): string
    {
        return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, trim($path));
    }

    private function humanFileSize(int $bytes): string
    {
        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($bytes < 1024 || $unit === 'GB') {
                return number_format($bytes, $unit === 'B' ? 0 : 2, ',', '.') . ' ' . $unit;
            }
            $bytes = (int) round($bytes / 1024);
        }

        return $bytes . ' B';
    }
}
