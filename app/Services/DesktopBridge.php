<?php

namespace App\Services;

use Throwable;

class DesktopBridge
{
    private const SYSTEM_CLASSES = [
        'Native\\Desktop\\Facades\\System',
        'Native\\Laravel\\Facades\\System',
    ];

    private const DIALOG_CLASSES = [
        'Native\\Desktop\\Dialog',
        'Native\\Desktop\\Facades\\Dialog',
        'Native\\Laravel\\Facades\\Dialog',
    ];

    public function available(): bool
    {
        return $this->systemClass() !== null || $this->dialogClass() !== null;
    }

    public function printers(): array
    {
        $system = $this->systemClass();

        if ($system === null) {
            return $this->fallbackPrinters();
        }

        try {
            $names = [];

            foreach ($system::printers() as $printer) {
                $name = $this->printerName($printer);
                if ($name !== '') {
                    $names[] = $name;
                }
            }

            $names = array_values(array_unique($names));
            sort($names, SORT_NATURAL | SORT_FLAG_CASE);

            return $names;
        } catch (Throwable) {
            return $this->fallbackPrinters();
        }
    }

    public function printHtml(string $html, ?string $printerName = null, array $settings = []): bool
    {
        $system = $this->systemClass();

        if ($system === null) {
            return false;
        }

        try {
            $system::print($html, $this->findPrinter($printerName), $settings);
            return true;
        } catch (Throwable $exception) {
            report($exception);
            return false;
        }
    }


    public function selectSaveFile(
        string $title,
        string $defaultFileName,
        array $extensions = [],
        ?string $defaultDirectory = null
    ): ?string {
        $fallbackDirectory = ($defaultDirectory && is_dir($defaultDirectory))
            ? $defaultDirectory
            : storage_path('app/exports');

        if (! is_dir($fallbackDirectory)) {
            @mkdir($fallbackDirectory, 0775, true);
        }

        $fallbackPath = rtrim($fallbackDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $defaultFileName;
        $dialogClass = $this->dialogClass();

        if ($dialogClass === null) {
            return $fallbackPath;
        }

        try {
            $dialog = $dialogClass::new()
                ->title($title)
                ->button('Simpan');

            if ($extensions !== [] && method_exists($dialog, 'filter')) {
                $dialog = $dialog->filter('File', $extensions);
            }

            if (method_exists($dialog, 'defaultPath')) {
                $defaultPath = $defaultFileName;
                if ($defaultDirectory && is_dir($defaultDirectory)) {
                    $defaultPath = rtrim($defaultDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $defaultFileName;
                }
                $dialog = $dialog->defaultPath($defaultPath);
            }

            if (method_exists($dialog, 'save')) {
                $selected = $this->normalizeSelection($dialog->save());
                return $selected ?: $fallbackPath;
            }

            $folder = $this->selectFolder($defaultDirectory);
            return $folder
                ? rtrim($folder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $defaultFileName
                : $fallbackPath;
        } catch (Throwable $exception) {
            report($exception);
            return $fallbackPath;
        }
    }

    public function selectExportDirectory(?string $defaultDirectory = null, string $title = 'Pilih Folder Penyimpanan Export'): ?string
    {
        $dialogClass = $this->dialogClass();

        if ($dialogClass === null) {
            return null;
        }

        try {
            $dialog = $dialogClass::new()
                ->title($title)
                ->button('Pilih Folder');

            if ($defaultDirectory && is_dir($defaultDirectory) && method_exists($dialog, 'defaultPath')) {
                $dialog = $dialog->defaultPath($defaultDirectory);
            }

            if (method_exists($dialog, 'folders')) {
                $dialog = $dialog->folders();
            } elseif (method_exists($dialog, 'folder')) {
                $dialog = $dialog->folder();
            }

            return $this->normalizeSelection($dialog->open());
        } catch (Throwable $exception) {
            report($exception);
            return null;
        }
    }

    public function selectFolder(?string $defaultPath = null): ?string
    {
        $dialogClass = $this->dialogClass();
        if ($dialogClass === null) {
            return null;
        }

        try {
            $dialog = $dialogClass::new()
                ->title('Pilih Folder Data dan Cadangan')
                ->button('Pilih Folder');

            if ($defaultPath && is_dir($defaultPath)) {
                $dialog = $dialog->defaultPath($defaultPath);
            }

            if (method_exists($dialog, 'folders')) {
                $dialog = $dialog->folders();
            } elseif (method_exists($dialog, 'folder')) {
                $dialog = $dialog->folder();
            }

            return $this->normalizeSelection($dialog->open());
        } catch (Throwable $exception) {
            report($exception);
            return null;
        }
    }

    public function selectBackupFile(?string $defaultPath = null): ?string
    {
        $dialogClass = $this->dialogClass();
        if ($dialogClass === null) {
            return null;
        }

        try {
            $dialog = $dialogClass::new()
                ->title('Pilih File Cadangan TB 39')
                ->button('Pilih File')
                ->filter('Database', ['sqlite', 'db', 'sql']);

            if ($defaultPath && is_dir($defaultPath)) {
                $dialog = $dialog->defaultPath($defaultPath);
            }

            if (method_exists($dialog, 'file')) {
                $dialog = $dialog->file();
            }

            return $this->normalizeSelection($dialog->open());
        } catch (Throwable $exception) {
            report($exception);
            return null;
        }
    }

    // === TB39 SAFE EXPORT DIALOG START ===
    public function shouldUseNativeDialogs(mixed $request = null): bool
    {
        if ($this->dialogClass() === null) {
            return false;
        }

        $userAgent = '';
        if (is_object($request) && method_exists($request, 'userAgent')) {
            $userAgent = (string) $request->userAgent();
        } elseif (isset($_SERVER['HTTP_USER_AGENT'])) {
            $userAgent = (string) $_SERVER['HTTP_USER_AGENT'];
        }

        $headers = strtolower(
            $userAgent . ' ' .
            (string) ($_SERVER['HTTP_X_NATIVEPHP'] ?? '') . ' ' .
            (string) ($_SERVER['HTTP_X_ELECTRON'] ?? '') . ' ' .
            (string) ($_SERVER['HTTP_SEC_CH_UA_PLATFORM'] ?? '')
        );

        if (str_contains($headers, 'electron') || str_contains($headers, 'nativephp')) {
            return true;
        }

        $nativeFlag = strtolower((string) (
            getenv('NATIVEPHP_RUNNING') ?:
            getenv('NATIVEPHP_DESKTOP') ?:
            ($_SERVER['NATIVEPHP_RUNNING'] ?? '') ?:
            ($_SERVER['NATIVEPHP_DESKTOP'] ?? '')
        ));

        return in_array($nativeFlag, ['1', 'true', 'yes', 'desktop'], true);
    }

    public function selectExportFolder(string $title = 'Pilih Folder Penyimpanan Export', ?string $defaultPath = null): ?string
    {
        $dialogClass = $this->dialogClass();

        if ($dialogClass === null) {
            return null;
        }

        try {
            $dialog = $dialogClass::new()
                ->title($title)
                ->button('Pilih Folder');

            if ($defaultPath && is_dir($defaultPath) && method_exists($dialog, 'defaultPath')) {
                $dialog = $dialog->defaultPath($defaultPath);
            }

            if (method_exists($dialog, 'folders')) {
                $dialog = $dialog->folders();
            } elseif (method_exists($dialog, 'folder')) {
                $dialog = $dialog->folder();
            }

            return $this->normalizeSelection($dialog->open());
        } catch (Throwable $exception) {
            report($exception);
            return null;
        }
    }
    // === TB39 SAFE EXPORT DIALOG END ===


    private function systemClass(): ?string
    {
        foreach (self::SYSTEM_CLASSES as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        return null;
    }

    private function dialogClass(): ?string
    {
        foreach (self::DIALOG_CLASSES as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        return null;
    }

    private function findPrinter(?string $printerName): mixed
    {
        if (! $printerName) {
            return null;
        }

        $system = $this->systemClass();
        if ($system === null) {
            return null;
        }

        foreach ($system::printers() as $printer) {
            if ($this->printerName($printer) === $printerName) {
                return $printer;
            }
        }

        return null;
    }

    private function printerName(mixed $printer): string
    {
        if (is_object($printer)) {
            foreach (['displayName', 'name', 'deviceName'] as $property) {
                if (isset($printer->{$property}) && is_string($printer->{$property})) {
                    return trim($printer->{$property});
                }
            }
        }

        if (is_array($printer)) {
            foreach (['displayName', 'name', 'deviceName'] as $key) {
                if (isset($printer[$key]) && is_string($printer[$key])) {
                    return trim($printer[$key]);
                }
            }
        }

        return '';
    }

    private function normalizeSelection(mixed $selection): ?string
    {
        if (is_array($selection)) {
            $selection = $selection[0] ?? null;
        }

        if (! is_string($selection)) {
            return null;
        }

        $selection = trim($selection);
        return $selection !== '' ? $selection : null;
    }

    private function fallbackPrinters(): array
    {
        $commands = PHP_OS_FAMILY === 'Windows'
            ? [
                'powershell -NoProfile -ExecutionPolicy Bypass -Command "Get-Printer | Select-Object -ExpandProperty Name"',
                'wmic printer get name',
            ]
            : ['lpstat -a 2>/dev/null'];

        foreach ($commands as $command) {
            $output = [];
            $exitCode = 1;

            try {
                exec($command, $output, $exitCode);
            } catch (Throwable) {
                continue;
            }

            if ($exitCode !== 0) {
                continue;
            }

            $names = [];
            foreach ($output as $line) {
                $line = trim((string) $line);
                if ($line === '' || strcasecmp($line, 'Name') === 0) {
                    continue;
                }

                if (PHP_OS_FAMILY !== 'Windows') {
                    $line = preg_split('/\s+/', $line)[0] ?? '';
                }

                if ($line !== '') {
                    $names[] = $line;
                }
            }

            if ($names !== []) {
                $names = array_values(array_unique($names));
                sort($names, SORT_NATURAL | SORT_FLAG_CASE);
                return $names;
            }
        }

        return [];
    }
}
