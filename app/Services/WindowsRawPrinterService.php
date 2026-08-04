<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class WindowsRawPrinterService
{
    /**
     * Mengirim byte teks langsung ke Windows Print Spooler dalam mode RAW.
     * Tidak menambahkan form-feed, sehingga kertas continuous berhenti pada
     * akhir konten yang dikirim.
     */
    public function print(?string $printerName, string $content, string $documentName = 'TB39 Print Job'): bool
    {
        $printerName = trim((string) $printerName);

        if (PHP_OS_FAMILY !== 'Windows' || $printerName === '' || ! function_exists('proc_open')) {
            return false;
        }

        $directory = storage_path('app/print_jobs');
        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            return false;
        }

        $token = date('Ymd_His') . '_' . uniqid('', true);
        $token = preg_replace('/[^A-Za-z0-9_.-]/', '_', $token) ?: date('Ymd_His');
        $dataPath = $directory . DIRECTORY_SEPARATOR . 'tb39_' . $token . '.prn';
        $scriptPath = $directory . DIRECTORY_SEPARATOR . 'tb39_' . $token . '.ps1';

        try {
            $normalized = preg_replace("~\r\n|\r|\n~", "\r\n", $content) ?? $content;
            $normalized = rtrim($normalized, "\r\n") . "\r\n";

            // ESC @ menginisialisasi Epson. Tidak ada FF (0x0C) dan tidak ada cut.
            $payload = "\x1B@" . $normalized;
            $encoded = @iconv('UTF-8', 'CP437//TRANSLIT//IGNORE', $payload);
            if (is_string($encoded) && $encoded !== '') {
                $payload = $encoded;
            }

            if (@file_put_contents($dataPath, $payload, LOCK_EX) === false) {
                return false;
            }

            if (@file_put_contents($scriptPath, $this->powershellScript(), LOCK_EX) === false) {
                return false;
            }

            $powershell = rtrim((string) getenv('SystemRoot'), '\\/')
                . '\\System32\\WindowsPowerShell\\v1.0\\powershell.exe';
            if (! is_file($powershell)) {
                $powershell = 'powershell.exe';
            }

            $command = [
                $powershell,
                '-NoLogo',
                '-NoProfile',
                '-NonInteractive',
                '-ExecutionPolicy',
                'Bypass',
                '-File',
                $scriptPath,
                '-PrinterName',
                $printerName,
                '-DataPath',
                $dataPath,
                '-DocumentName',
                $documentName,
            ];

            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $process = @proc_open($command, $descriptors, $pipes, null, null, ['bypass_shell' => true]);
            if (! is_resource($process)) {
                return false;
            }

            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            $exitCode = proc_close($process);
            if ($exitCode !== 0) {
                report(new RuntimeException(
                    'RAW print gagal (exit ' . $exitCode . '): ' . trim((string) ($stderr ?: $stdout))
                ));

                return false;
            }

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        } finally {
            @unlink($dataPath);
            @unlink($scriptPath);
        }
    }

    private function powershellScript(): string
    {
        return <<<'POWERSHELL'
param(
    [Parameter(Mandatory = $true)][string]$PrinterName,
    [Parameter(Mandatory = $true)][string]$DataPath,
    [Parameter(Mandatory = $true)][string]$DocumentName
)

$source = @"
using System;
using System.Runtime.InteropServices;

public static class TB39RawPrinter
{
    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Ansi)]
    public class DOCINFOA
    {
        [MarshalAs(UnmanagedType.LPStr)] public string pDocName;
        [MarshalAs(UnmanagedType.LPStr)] public string pOutputFile;
        [MarshalAs(UnmanagedType.LPStr)] public string pDataType;
    }

    [DllImport("winspool.Drv", EntryPoint = "OpenPrinterA", SetLastError = true, CharSet = CharSet.Ansi)]
    private static extern bool OpenPrinter(string printerName, out IntPtr printerHandle, IntPtr defaults);

    [DllImport("winspool.Drv", SetLastError = true)]
    private static extern bool ClosePrinter(IntPtr printerHandle);

    [DllImport("winspool.Drv", EntryPoint = "StartDocPrinterA", SetLastError = true, CharSet = CharSet.Ansi)]
    private static extern int StartDocPrinter(IntPtr printerHandle, int level, [In, MarshalAs(UnmanagedType.LPStruct)] DOCINFOA docInfo);

    [DllImport("winspool.Drv", SetLastError = true)]
    private static extern bool EndDocPrinter(IntPtr printerHandle);

    [DllImport("winspool.Drv", SetLastError = true)]
    private static extern bool StartPagePrinter(IntPtr printerHandle);

    [DllImport("winspool.Drv", SetLastError = true)]
    private static extern bool EndPagePrinter(IntPtr printerHandle);

    [DllImport("winspool.Drv", SetLastError = true)]
    private static extern bool WritePrinter(IntPtr printerHandle, IntPtr bytes, int count, out int written);

    public static bool Send(string printerName, string documentName, byte[] data)
    {
        IntPtr printerHandle = IntPtr.Zero;
        IntPtr unmanagedBytes = IntPtr.Zero;
        bool documentStarted = false;
        bool pageStarted = false;

        try
        {
            if (!OpenPrinter(printerName, out printerHandle, IntPtr.Zero))
                return false;

            var docInfo = new DOCINFOA
            {
                pDocName = documentName,
                pOutputFile = null,
                pDataType = "RAW"
            };

            if (StartDocPrinter(printerHandle, 1, docInfo) == 0)
                return false;
            documentStarted = true;

            if (!StartPagePrinter(printerHandle))
                return false;
            pageStarted = true;

            unmanagedBytes = Marshal.AllocCoTaskMem(data.Length);
            Marshal.Copy(data, 0, unmanagedBytes, data.Length);

            int written;
            return WritePrinter(printerHandle, unmanagedBytes, data.Length, out written)
                && written == data.Length;
        }
        finally
        {
            if (unmanagedBytes != IntPtr.Zero)
                Marshal.FreeCoTaskMem(unmanagedBytes);
            if (pageStarted)
                EndPagePrinter(printerHandle);
            if (documentStarted)
                EndDocPrinter(printerHandle);
            if (printerHandle != IntPtr.Zero)
                ClosePrinter(printerHandle);
        }
    }
}
"@

Add-Type -TypeDefinition $source -Language CSharp
[byte[]]$bytes = [System.IO.File]::ReadAllBytes($DataPath)

if (-not [TB39RawPrinter]::Send($PrinterName, $DocumentName, $bytes)) {
    $errorCode = [Runtime.InteropServices.Marshal]::GetLastWin32Error()
    Write-Error "Windows RAW printing failed. Win32 error: $errorCode"
    exit 2
}

exit 0
POWERSHELL;
    }
}
