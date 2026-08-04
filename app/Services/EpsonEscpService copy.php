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
        
        // Perintah ESC/P awal untuk mengaktifkan Bold secara global
        $out[] = "\x1B\x45";

        // ================= HEADER =================
        // Super Bold = Bold ON (\x1B\x45) + Double Strike ON (\x1B\x47) + Double Width ON (\x1B\x57\x01) + Double Height ON (\x1B\x77\x01)
        $bigOn = "\x1B\x45\x1B\x47\x1B\x57\x01\x1B\x77\x01";
        // Normalkan ukuran (Double Strike OFF \x1B\x48, Double Width OFF, Double Height OFF). Bold tetap ON.
        $bigOff = "\x1B\x48\x1B\x57\x00\x1B\x77\x00";

        // Baris ini ukurannya besar, jadi kita bagi lebarnya jadi 19 dan 20 (Total 39 karakter)
        $out[] = $bigOn . $this->pad('TOKO BANGUNAN 39', 19) . $this->pad('Faktur/Surat Jalan', 20, ' ', STR_PAD_LEFT) . $bigOff;
        
        // Baris di bawahnya kembali ke ukuran normal (Total 79 karakter)
        $out[] = $this->pad('Jl. Pramuka Cikondang Kota Sukabumi', 40) . $this->pad('No. ' . ($sale['no_invoice'] ?? '-'), 39, ' ', STR_PAD_LEFT);
        $out[] = 'Telp: 085659599869';
        $out[] = '';

        // ================= INFO PELANGGAN & TRANSAKSI =================
        $out[] = '┌──────────────────────────────────────┬──────────────────────────────────────┐';
        $out[] = '│' . $this->pad(' Data Pelanggan', 38) . '│' . $this->pad(' Data Transaksi', 38) . '│';
        $out[] = '├──────────────────────────────────────┼──────────────────────────────────────┤';
        
        $pembeli = $sale['nama_pelanggan'] ?? '-';
        $sales   = $sale['nama_sales'] ?? '-';
        $out[] = '│' . $this->pad(' Pembeli: ' . $this->pad($pembeli, 28), 38) . '│' . $this->pad(' Sales      : ' . $this->pad($sales, 23), 38) . '│';
        
        $alamat = $sale['alamat_pelanggan'] ?? '-';
        $bayar  = ($sale['metode_bayar'] ?? '-') . ' / ' . ($sale['status'] ?? '-');
        $out[] = '│' . $this->pad(' Alamat : ' . $this->pad($alamat, 28), 38) . '│' . $this->pad(' Pembayaran : ' . $this->pad($bayar, 23), 38) . '│';
        
        $tgl = !empty($sale['tgl_transaksi']) ? date('d-m-Y', strtotime((string) $sale['tgl_transaksi'])) : '-';
        $out[] = '│' . $this->pad('', 38) . '│' . $this->pad(' Tanggal    : ' . $this->pad($tgl, 23), 38) . '│';
        $out[] = '└──────────────────────────────────────┴──────────────────────────────────────┘';
        
        // ================= TABEL BARANG =================
        // Lebar kolom disesuaikan: No (3), Nama Barang (27), Qty (5), Satuan (8), Harga (13), Jumlah (16)
        $out[] = '┌───┬───────────────────────────┬─────┬────────┬─────────────┬────────────────┐';
        $out[] = '│' . 
            $this->pad(' No', 3) . '│' . 
            $this->pad(' Nama Barang', 27) . '│' . 
            $this->pad(' Qty', 5) . '│' . 
            $this->pad(' Satuan', 8) . '│' . 
            $this->pad('Harga', 13, ' ', STR_PAD_BOTH) . '│' . // Rata Tengah
            $this->pad('Jumlah', 16, ' ', STR_PAD_BOTH) . '│'; // Rata Tengah
        $out[] = '├───┼───────────────────────────┼─────┼────────┼─────────────┼────────────────┤';
        
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
                     $this->pad(' ' . $number++, 3) . '│' . 
                     $this->pad(' ' . $name, 27) . '│' . 
                     $this->pad($this->formatQty($qty) . ' ', 5, ' ', STR_PAD_LEFT) . '│' . 
                     $this->pad(' ' . $unit, 8) . '│' . 
                     $this->pad(' ' . number_format($price, 0, ',', '.'), 13, ' ', STR_PAD_RIGHT) . '│' . // Rata Kiri
                     $this->pad(' ' . number_format($subtotal, 0, ',', '.'), 16, ' ', STR_PAD_RIGHT) . '│'; // Rata Kiri
            
            $currentIndex++;
            // Tambahkan garis tengah jika ini BUKAN baris barang terakhir
            if ($currentIndex < $itemsCount) {
                $out[] = '├───┼───────────────────────────┼─────┼────────┼─────────────┼────────────────┤';
            }
        }
        
        // Garis batas sebelum TOTAL
        $out[] = '├───┴───────────────────────────┴─────┴────────┴─────────────┼────────────────┤';
        $out[] = '│' . $this->pad('GRAND TOTAL ', 60, ' ', STR_PAD_LEFT) . '│' . $this->pad(' Rp ' . number_format($netTotal, 0, ',', '.'), 16, ' ', STR_PAD_RIGHT) . '│';
        $out[] = '└────────────────────────────────────────────────────────────┴────────────────┘';
        
        // ================= CATATAN PEMBAYARAN =================
        $out[] = '┌─────────────────────────────────────────────────────────────────────────────┐';
        $out[] = '│' . $this->pad(' Catatan Pembayaran:', 77) . '│';
        $out[] = '│' . $this->pad(' Transfer dapat dilakukan ke rekening berikut:', 77) . '│';
        $out[] = '│' . $this->pad(' BCA: 3770322099 ', 77) . '│';
        $out[] = '│' . $this->pad(' BRI: 009201112697508', 77) . '│';
        $out[] = '│' . $this->pad(' Atas nama RIFKY FACHREZA', 77) . '│';

        if ($refund > 0) {
            $out[] = '├─────────────────────────────────────────────────────────────────────────────┤';
            $out[] = '│' . $this->pad(' * Nilai refund: Rp ' . number_format($refund, 0, ',', '.'), 77) . '│';
        }
        $out[] = '└─────────────────────────────────────────────────────────────────────────────┘';
        $out[] = '';
        
        // ================= TANDA TANGAN =================
        $out[] = '   ' . $this->pad('Diterima Oleh,', 22, ' ', STR_PAD_BOTH) . 
                 '   ' . $this->pad('Diserahkan Oleh,', 22, ' ', STR_PAD_BOTH) . 
                 '   ' . $this->pad('Disiapkan Oleh,', 22, ' ', STR_PAD_BOTH);
        $out[] = '';
        $out[] = '';
        $out[] = '';
        $out[] = '   ' . $this->pad('(____________________)', 22, ' ', STR_PAD_BOTH) . 
                 '   ' . $this->pad('(____________________)', 22, ' ', STR_PAD_BOTH) . 
                 '   ' . $this->pad('Admin', 22, ' ', STR_PAD_BOTH);

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
            
            // Perintah ESC/P awal untuk mengaktifkan Bold secara global per rangkap
            $out[] = "\x1B\x45";
            
            // Header
            $bigOn = "\x1B\x45\x1B\x47\x1B\x57\x01\x1B\x77\x01";
            $bigOff = "\x1B\x48\x1B\x57\x00\x1B\x77\x00";

            $out[] = $bigOn . $this->pad('TOKO BANGUNAN 39', 19) . $this->pad('Surat Jalan / Faktur', 20, ' ', STR_PAD_LEFT) . $bigOff;
            
            $out[] = $this->pad('Jl. Pramuka Cikondang Kota Sukabumi', 40) . $this->pad($labels[$copy] ?? ('SALINAN ' . ($copy + 1)), 39, ' ', STR_PAD_LEFT);
            $out[] = 'Telp: 085659599869';
            $out[] = '';

            // Info Box
            $out[] = '┌──────────────────────────────────────┬──────────────────────────────────────┐';
            $out[] = '│' . $this->pad(' Data Pengiriman', 38) . '│' . $this->pad(' Data Transaksi', 38) . '│';
            $out[] = '├──────────────────────────────────────┼──────────────────────────────────────┤';
            
            $pembeli = $delivery->nama_pelanggan ?? '-';
            $alamat  = $delivery->alamat_tujuan ?? '-';
            $sopir   = $delivery->nama_sopir ?? '-';
            $plat    = $delivery->plat_nomor ?? '-';
            $noInv   = $delivery->no_invoice ?? '-';
            $noSJ    = $delivery->no_surat_jalan ?? '-';
            $tgl     = !empty($delivery->tgl_terbit) ? date('d-m-Y', strtotime((string) $delivery->tgl_terbit)) : '-';

            $out[] = '│' . $this->pad(' Pembeli: ' . $this->pad($pembeli, 27), 38) . '│' . $this->pad(' No SJ   : ' . $this->pad($noSJ, 26), 38) . '│';
            $out[] = '│' . $this->pad(' Alamat : ' . $this->pad($alamat, 27), 38) . '│' . $this->pad(' No Inv  : ' . $this->pad($noInv, 26), 38) . '│';
            $out[] = '│' . $this->pad(' Sopir  : ' . $this->pad($sopir, 27), 38) . '│' . $this->pad(' Tanggal : ' . $this->pad($tgl, 26), 38) . '│';
            $out[] = '│' . $this->pad(' Plat   : ' . $this->pad($plat, 27), 38) . '│' . $this->pad('', 38) . '│';
            $out[] = '└──────────────────────────────────────┴──────────────────────────────────────┘';
            
            // Table
            // Lebar kolom disesuaikan: No (3), Nama Barang (31), Qty (5), Satuan (8), Harga (11), Jumlah (14)
            $out[] = '┌───┬───────────────────────────────┬─────┬────────┬───────────┬──────────────┐';
            $out[] = '│' . 
                $this->pad(' No', 3) . '│' . 
                $this->pad(' Nama Barang', 31) . '│' . 
                $this->pad(' Qty', 5) . '│' . 
                $this->pad(' Satuan', 8) . '│' . 
                $this->pad('Harga', 11, ' ', STR_PAD_BOTH) . '│' . // Rata Tengah
                $this->pad('Jumlah', 14, ' ', STR_PAD_BOTH) . '│'; // Rata Tengah
            $out[] = '├───┼───────────────────────────────┼─────┼────────┼───────────┼──────────────┤';

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
                    $this->pad(' ' . ($index + 1), 3) . '│' . 
                    $this->pad(' ' . $name, 31) . '│' . 
                    $this->pad($this->formatQty($qty) . ' ', 5, ' ', STR_PAD_LEFT) . '│' . 
                    $this->pad(' ' . $unit, 8) . '│' . 
                    $this->pad(' ' . number_format($price, 0, ',', '.'), 11, ' ', STR_PAD_RIGHT) . '│' . // Rata Kiri
                    $this->pad(' ' . number_format($subtotal, 0, ',', '.'), 14, ' ', STR_PAD_RIGHT) . '│'; // Rata Kiri

                $currentIndex++;
                // Tambahkan garis tengah jika ini BUKAN baris barang terakhir
                if ($currentIndex < $itemsCount) {
                    $out[] = '├───┼───────────────────────────────┼─────┼────────┼───────────┼──────────────┤';
                }
            }

            $out[] = '├───┴───────────────────────────────┴─────┴────────┴───────────┼──────────────┤';
            $out[] = '│' . $this->pad('GRAND TOTAL ', 62, ' ', STR_PAD_LEFT) . '│' . $this->pad(' Rp ' . number_format($grandTotal, 0, ',', '.'), 14, ' ', STR_PAD_RIGHT) . '│';
            $out[] = '└──────────────────────────────────────────────────────────────┴──────────────┘';
            
            if (!empty($delivery->catatan)) {
                $out[] = 'Catatan: ' . $this->pad((string)$delivery->catatan, 70);
                $out[] = '';
            }

            // TTD
            $out[] = '   ' . $this->pad('Diterima Oleh,', 22, ' ', STR_PAD_BOTH) . 
                     '   ' . $this->pad('Diserahkan Oleh,', 22, ' ', STR_PAD_BOTH) . 
                     '   ' . $this->pad('Disiapkan Oleh,', 22, ' ', STR_PAD_BOTH);
            $out[] = '';
            $out[] = '';
            $out[] = '';
            $out[] = '   ' . $this->pad('(____________________)', 22, ' ', STR_PAD_BOTH) . 
                     '   ' . $this->pad('(____________________)', 22, ' ', STR_PAD_BOTH) . 
                     '   ' . $this->pad('Kasir TB 39', 22, ' ', STR_PAD_BOTH);

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