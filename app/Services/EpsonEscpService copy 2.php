<?php

namespace App\Services;

class EpsonEscpService
{
    private const WIDTH = 79;

    /**
     * Membuat faktur teks 80 kolom untuk printer dot-matrix Epson LQ.
     * Hasil tidak mengandung form-feed atau perintah cut.
     */
    public function generateInvoice(array $sale, iterable $details): string
    {
        $out = [];
        
        // Perintah ESC/P awal untuk mengaktifkan Bold secara global, Condensed ON (\x0F), dan Line Spacing Rapat (\x1B\x30)
        $out[] = "\x1B\x45\x0F\x1B\x30";

        // ================= HEADER =================
        // Super Bold = Bold ON (\x1B\x45) + Double Strike ON (\x1B\x47) + Double Width ON (\x1B\x57\x01) + Double Height ON (\x1B\x77\x01)
        $bigOn = "\x1B\x45\x1B\x47\x1B\x57\x01\x1B\x77\x01";
        // Normalkan ukuran (Double Strike OFF \x1B\x48, Double Width OFF, Double Height OFF). Bold tetap ON.
        $bigOff = "\x1B\x48\x1B\x57\x00\x1B\x77\x00";

        // Baris ini ukurannya besar (Double Width), jadi dalam Condensed Mode total lebarnya 66 karakter double-width (32 + 34)
        $out[] = $bigOn . $this->pad('TOKO BANGUNAN 39', 32) . $this->pad('Faktur/Surat Jalan', 34, ' ', STR_PAD_LEFT) . $bigOff;
        
        // Baris di bawahnya menggunakan normal-condensed (Total 132 karakter: 70 + 62)
        $out[] = $this->pad('Jl. Pramuka Cikondang Kota Sukabumi', 70) . $this->pad('No. ' . ($sale['no_invoice'] ?? '-'), 62, ' ', STR_PAD_LEFT);
        $out[] = $this->pad('Telp: 085659599869', 132);
        $out[] = '';

        // ================= INFO PELANGGAN & TRANSAKSI =================
        $out[] = '┌' . str_repeat('─', 64) . '┬' . str_repeat('─', 65) . '┐';
        $out[] = '│' . $this->pad(' Data Pelanggan', 64) . '│' . $this->pad(' Data Transaksi', 65) . '│';
        $out[] = '├' . str_repeat('─', 64) . '┼' . str_repeat('─', 65) . '┤';
        
        $pembeli = $sale['nama_pelanggan'] ?? '-';
        $sales   = $sale['nama_sales'] ?? '-';
        $out[] = '│' . $this->pad(' Pembeli: ' . $pembeli, 64) . '│' . $this->pad(' Sales      : ' . $sales, 65) . '│';
        
        $alamat = $sale['alamat_pelanggan'] ?? '-';
        $bayar  = ($sale['metode_bayar'] ?? '-') . ' / ' . ($sale['status'] ?? '-');
        $out[] = '│' . $this->pad(' Alamat : ' . $alamat, 64) . '│' . $this->pad(' Pembayaran : ' . $bayar, 65) . '│';
        
        $tgl = !empty($sale['tgl_transaksi']) ? date('d-m-Y', strtotime((string) $sale['tgl_transaksi'])) : '-';
        $out[] = '│' . $this->pad('', 64) . '│' . $this->pad(' Tanggal    : ' . $tgl, 65) . '│';
        $out[] = '└' . str_repeat('─', 64) . '┴' . str_repeat('─', 65) . '┘';
        
        // ================= TABEL BARANG =================
        // Lebar kolom disesuaikan untuk 132 karakter: No (4), Nama Barang (67), Qty (8), Satuan (10), Harga (17), Jumlah (19)
        $out[] = '┌' . str_repeat('─', 4) . '┬' . str_repeat('─', 67) . '┬' . str_repeat('─', 8) . '┬' . str_repeat('─', 10) . '┬' . str_repeat('─', 17) . '┬' . str_repeat('─', 19) . '┐';
        $out[] = '│' . 
            $this->pad(' No', 4) . '│' . 
            $this->pad(' Nama Barang', 67) . '│' . 
            $this->pad('Qty', 8, ' ', STR_PAD_BOTH) . '│' . // Rata Tengah
            $this->pad(' Satuan', 10) . '│' . 
            $this->pad('Harga', 17, ' ', STR_PAD_BOTH) . '│' . // Rata Tengah
            $this->pad('Jumlah', 19, ' ', STR_PAD_BOTH) . '│'; // Rata Tengah
        $out[] = '├' . str_repeat('─', 4) . '┼' . str_repeat('─', 67) . '┼' . str_repeat('─', 8) . '┼' . str_repeat('─', 10) . '┼' . str_repeat('─', 17) . '┼' . str_repeat('─', 19) . '┤';
        
        $number = 1;
        $total = (int) ($sale['total_belanja'] ?? 0);
        $refund = (int) ($sale['refund_total'] ?? 0);
        $netTotal = max(0, $total - $refund);
        
        $itemsCount = count($details);
        $currentIndex = 0;

        foreach ($details as $detail) {
            $name = $detail->product?->nama_barang ?? $detail->nama_barang ?? $detail->kode_barang ?? '-';
            $unit = $detail->product?->satuan ?? $detail->satuan ?? 'Unit';
            $qty = (float) ($detail->qty ?? 0);
            $price = (int) ($detail->harga_jual ?? 0);
            $subtotal = (int) ($detail->subtotal ?? round($qty * $price));

            $out[] = '│' . 
                     $this->pad(' ' . $number++, 4) . '│' . 
                     $this->pad(' ' . $name, 67) . '│' . 
                     $this->pad(' ' . $this->formatQty($qty), 8, ' ', STR_PAD_RIGHT) . '│' . // Rata Kiri dengan spasi awalan
                     $this->pad(' ' . $unit, 10) . '│' . 
                     $this->pad(' ' . number_format($price, 0, ',', '.'), 17, ' ', STR_PAD_RIGHT) . '│' . // Rata Kiri
                     $this->pad(' ' . number_format($subtotal, 0, ',', '.'), 19, ' ', STR_PAD_RIGHT) . '│'; // Rata Kiri
            
            $currentIndex++;
            // Tambahkan garis tengah jika ini BUKAN baris barang terakhir
            if ($currentIndex < $itemsCount) {
                $out[] = '├' . str_repeat('─', 4) . '┼' . str_repeat('─', 67) . '┼' . str_repeat('─', 8) . '┼' . str_repeat('─', 10) . '┼' . str_repeat('─', 17) . '┼' . str_repeat('─', 19) . '┤';
            }
        }
        
        // Garis batas sebelum TOTAL: Gabungan kolom 1-5 sepanjang 110 karakter (4+67+8+10+17 + 4 pembatas) + pembatas 1 + Jumlah 19
        $out[] = '├' . str_repeat('─', 110) . '┼' . str_repeat('─', 19) . '┤';
        $out[] = '│' . $this->pad('GRAND TOTAL', 110, ' ', STR_PAD_BOTH) . '│' . $this->pad(' Rp ' . number_format($netTotal, 0, ',', '.'), 19, ' ', STR_PAD_RIGHT) . '│';
        $out[] = '└' . str_repeat('─', 110) . '┴' . str_repeat('─', 19) . '┘';
        
        // ================= CATATAN PEMBAYARAN =================
        $out[] = '┌' . str_repeat('─', 130) . '┐';
        $out[] = '│' . $this->pad(' Catatan Pembayaran:', 130) . '│';
        $out[] = '│' . $this->pad(' Transfer dapat dilakukan ke rekening berikut:', 130) . '│';
        $out[] = '│' . $this->pad(' BCA: 3770322099 | BRI: 009201112697508 | Atas nama RIFKY FACHREZA', 130) . '│';

        if ($refund > 0) {
            $out[] = '├' . str_repeat('─', 130) . '┤';
            $out[] = '│' . $this->pad(' * Nilai refund: Rp ' . number_format($refund, 0, ',', '.'), 130) . '│';
        }
        $out[] = '└' . str_repeat('─', 130) . '┘';
        $out[] = '';
        
        // ================= TANDA TANGAN =================
        $out[] = '     ' . $this->pad('Diterima Oleh,', 36, ' ', STR_PAD_BOTH) . 
                 '     ' . $this->pad('Diserahkan Oleh,', 36, ' ', STR_PAD_BOTH) . 
                 '     ' . $this->pad('Disiapkan Oleh,', 36, ' ', STR_PAD_BOTH);
        $out[] = '';
        $out[] = '';
        $out[] = '';
        $out[] = '     ' . $this->pad('(____________________)', 36, ' ', STR_PAD_BOTH) . 
                 '     ' . $this->pad('(____________________)', 36, ' ', STR_PAD_BOTH) . 
                 '     ' . $this->pad('Admin', 36, ' ', STR_PAD_BOTH);

        // Kembalikan ke mode normal (Condensed OFF \x12, Jarak Baris Normal \x1B\x32)
        $out[] = "\x12\x1B\x32";

        return implode("\n", $out) . "\n";
    }

    /**
     * Membuat tiga rangkap surat jalan dalam satu aliran continuous.
     */
    public function generateDeliveryOrder(object $delivery, int $copies = 3): string
    {
        $labels = ['RANGKAP TOKO', 'RANGKAP SOPIR', 'RANGKAP PEMBELI'];
        $blocks = [];
        $copies = max(1, $copies);

        for ($copy = 0; $copy < $copies; $copy++) {
            $out = [];
            
            // Perintah ESC/P awal untuk mengaktifkan Bold secara global, Condensed ON (\x0F), dan Line Spacing Rapat (\x1B\x30) per rangkap
            $out[] = "\x1B\x45\x0F\x1B\x30";
            
            // Header
            $bigOn = "\x1B\x45\x1B\x47\x1B\x57\x01\x1B\x77\x01";
            $bigOff = "\x1B\x48\x1B\x57\x00\x1B\x77\x00";

            // Baris ini ukurannya besar (Double Width), jadi dalam Condensed Mode total lebarnya 66 karakter double-width (32 + 34)
            $out[] = $bigOn . $this->pad('TOKO BANGUNAN 39', 32) . $this->pad('Surat Jalan / Faktur', 34, ' ', STR_PAD_LEFT) . $bigOff;
            
            // Baris di bawahnya menggunakan normal-condensed (Total 132 karakter: 70 + 62)
            $out[] = $this->pad('Jl. Pramuka Cikondang Kota Sukabumi', 70) . $this->pad($labels[$copy] ?? ('SALINAN ' . ($copy + 1)), 62, ' ', STR_PAD_LEFT);
            $out[] = $this->pad('Telp: 085659599869', 132);
            $out[] = '';

            // Info Box
            $out[] = '┌' . str_repeat('─', 64) . '┬' . str_repeat('─', 65) . '┐';
            $out[] = '│' . $this->pad(' Data Pengiriman', 64) . '│' . $this->pad(' Data Transaksi', 65) . '│';
            $out[] = '├' . str_repeat('─', 64) . '┼' . str_repeat('─', 65) . '┤';
            
            $pembeli = $delivery->nama_pelanggan ?? '-';
            $alamat  = $delivery->alamat_tujuan ?? '-';
            $sopir   = $delivery->nama_sopir ?? '-';
            $plat    = $delivery->plat_nomor ?? '-';
            $noInv   = $delivery->no_invoice ?? '-';
            $noSJ    = $delivery->no_surat_jalan ?? '-';
            $tgl     = !empty($delivery->tgl_terbit) ? date('d-m-Y', strtotime((string) $delivery->tgl_terbit)) : '-';

            $out[] = '│' . $this->pad(' Pembeli: ' . $pembeli, 64) . '│' . $this->pad(' No SJ   : ' . $noSJ, 65) . '│';
            $out[] = '│' . $this->pad(' Alamat : ' . $alamat, 64) . '│' . $this->pad(' No Inv  : ' . $noInv, 65) . '│';
            $out[] = '│' . $this->pad(' Sopir  : ' . $sopir, 64) . '│' . $this->pad(' Tanggal : ' . $tgl, 65) . '│';
            $out[] = '│' . $this->pad(' Plat   : ' . $plat, 64) . '│' . $this->pad('', 65) . '│';
            $out[] = '└' . str_repeat('─', 64) . '┴' . str_repeat('─', 65) . '┘';
            
            // Table
            // Lebar kolom disesuaikan untuk 132 karakter: No (4), Nama Barang (67), Qty (8), Satuan (10), Harga (17), Jumlah (19)
            $out[] = '┌' . str_repeat('─', 4) . '┬' . str_repeat('─', 67) . '┬' . str_repeat('─', 8) . '┬' . str_repeat('─', 10) . '┬' . str_repeat('─', 17) . '┬' . str_repeat('─', 19) . '┐';
            $out[] = '│' . 
                $this->pad(' No', 4) . '│' . 
                $this->pad(' Nama Barang', 67) . '│' . 
                $this->pad('Qty', 8, ' ', STR_PAD_BOTH) . '│' . // Rata Tengah
                $this->pad(' Satuan', 10) . '│' . 
                $this->pad('Harga', 17, ' ', STR_PAD_BOTH) . '│' . // Rata Tengah
                $this->pad('Jumlah', 19, ' ', STR_PAD_BOTH) . '│'; // Rata Tengah
            $out[] = '├' . str_repeat('─', 4) . '┼' . str_repeat('─', 67) . '┼' . str_repeat('─', 8) . '┼' . str_repeat('─', 10) . '┼' . str_repeat('─', 17) . '┼' . str_repeat('─', 19) . '┤';

            $grandTotal = 0;
            $itemsCount = count($delivery->details ?? []);
            $currentIndex = 0;

            foreach ($delivery->details ?? [] as $index => $detail) {
                $name = $detail->product?->nama_barang ?? $detail->kode_barang ?? '-';
                $unit = $detail->product?->satuan ?? 'Unit';
                $qty = (float) ($detail->qty_kirim ?? 0);
                $subtotal = (int) ($detail->total_belanja ?? 0);
                $price = $qty > 0 ? (int) round($subtotal / $qty) : 0;
                $grandTotal += $subtotal;

                $out[] = '│' . 
                    $this->pad(' ' . ($index + 1), 4) . '│' . 
                    $this->pad(' ' . $name, 67) . '│' . 
                    $this->pad(' ' . $this->formatQty($qty), 8, ' ', STR_PAD_RIGHT) . '│' . // Rata Kiri dengan spasi awalan
                    $this->pad(' ' . $unit, 10) . '│' . 
                    $this->pad(' ' . number_format($price, 0, ',', '.'), 17, ' ', STR_PAD_RIGHT) . '│' . // Rata Kiri
                    $this->pad(' ' . number_format($subtotal, 0, ',', '.'), 19, ' ', STR_PAD_RIGHT) . '│'; // Rata Kiri

                $currentIndex++;
                // Tambahkan garis tengah jika ini BUKAN baris barang terakhir
                if ($currentIndex < $itemsCount) {
                    $out[] = '├' . str_repeat('─', 4) . '┼' . str_repeat('─', 67) . '┼' . str_repeat('─', 8) . '┼' . str_repeat('─', 10) . '┼' . str_repeat('─', 17) . '┼' . str_repeat('─', 19) . '┤';
                }
            }

            // Garis batas sebelum TOTAL: Gabungan kolom 1-5 sepanjang 110 karakter (4+67+8+10+17 + 4 pembatas) + pembatas 1 + Jumlah 19
            $out[] = '├' . str_repeat('─', 110) . '┼' . str_repeat('─', 19) . '┤';
            $out[] = '│' . $this->pad('GRAND TOTAL', 110, ' ', STR_PAD_BOTH) . '│' . $this->pad(' Rp ' . number_format($grandTotal, 0, ',', '.'), 19, ' ', STR_PAD_RIGHT) . '│';
            $out[] = '└' . str_repeat('─', 110) . '┴' . str_repeat('─', 19) . '┘';
            
            if (!empty($delivery->catatan)) {
                $out[] = 'Catatan: ' . $this->pad((string)$delivery->catatan, 120);
                $out[] = '';
            }

            // TTD
            $out[] = '     ' . $this->pad('Diterima Oleh,', 36, ' ', STR_PAD_BOTH) . 
                     '     ' . $this->pad('Diserahkan Oleh,', 36, ' ', STR_PAD_BOTH) . 
                     '     ' . $this->pad('Disiapkan Oleh,', 36, ' ', STR_PAD_BOTH);
            $out[] = '';
            $out[] = '';
            $out[] = '';
            $out[] = '     ' . $this->pad('(____________________)', 36, ' ', STR_PAD_BOTH) . 
                     '     ' . $this->pad('(____________________)', 36, ' ', STR_PAD_BOTH) . 
                     '     ' . $this->pad('Kasir TB 39', 36, ' ', STR_PAD_BOTH);

            // Kembalikan ke mode normal di akhir rangkap (Condensed OFF \x12, Jarak Baris Normal \x1B\x32)
            $out[] = "\x12\x1B\x32";

            $blocks[] = implode("\n", $out);
        }

        return implode("\n\n\n\n", $blocks) . "\n";
    }

    /**
     * Helper presisi untuk mengatur lebar kolom dan padding text
     */
    private function formatQty(float|int $qty): string
    {
        $val = (float) $qty;
        if (floor($val) == $val) {
            return number_format($val, 0, ',', '.');
        }
        return rtrim(rtrim(number_format($val, 3, ',', '.'), '0'), ',');
    }

    private function pad(string $text, int $len, string $pad = ' ', int $type = STR_PAD_RIGHT): string
    {
        if (function_exists('mb_strimwidth') && function_exists('mb_strlen')) {
            $text = mb_strimwidth($text, 0, $len, '', 'UTF-8');
            $padding = $len - mb_strlen($text, 'UTF-8');
        } else {
            $text = substr($text, 0, $len);
            $padding = $len - strlen($text);
        }

        if ($padding <= 0) {
            return $text;
        }

        if ($type === STR_PAD_RIGHT) {
            return $text . str_repeat($pad, $padding);
        } elseif ($type === STR_PAD_LEFT) {
            return str_repeat($pad, $padding) . $text;
        } else {
            $left = (int) floor($padding / 2);
            $right = $padding - $left;
            return str_repeat($pad, $left) . $text . str_repeat($pad, $right);
        }
    }
}