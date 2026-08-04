<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{

    private function withoutRefundedSales($query, string $alias = '')
    {
        // Refund sebagian tetap harus masuk dashboard sebagai omzet netto.
        return $query;
    }

    public function index(Request $request)
    {
        $filter = $request->query('filter', 'mingguan');
        if (! in_array($filter, ['mingguan', 'bulanan'], true)) {
            $filter = 'mingguan';
        }

        $today = Carbon::today();
        $days = $filter === 'bulanan' ? 30 : 7;
        $startDate = Carbon::today()->subDays($days - 1)->startOfDay();
        $endDate = Carbon::today()->endOfDay();

        $totalOmset = 0;
        $piutangBerjalan = 0;
        $notaTerbit = 0;
        $transaksiTerbaru = collect();
        $chartDataLabels = [];
        $chartDataValues = [];
        $chartNotaValues = [];

        if (Schema::hasTable('tbl_penjualan')) {
            $netSalesExpression = Schema::hasColumn('tbl_penjualan', 'refund_total')
                ? 'CASE WHEN COALESCE(total_belanja, 0) > COALESCE(refund_total, 0) THEN COALESCE(total_belanja, 0) - COALESCE(refund_total, 0) ELSE 0 END'
                : 'COALESCE(total_belanja, 0)';
            $totalOmset = (float) DB::table('tbl_penjualan')
                ->whereDate('tgl_transaksi', $today->toDateString())
                ->sum(DB::raw($netSalesExpression));

            $notaTerbit = (int) DB::table('tbl_penjualan')
                ->whereDate('tgl_transaksi', $today->toDateString())
                ->count();

            if (Schema::hasColumn('tbl_penjualan', 'sisa_piutang')) {
                $piutangBerjalan = (float) DB::table('tbl_penjualan')
                    ->where(function ($query) {
                        $query->where('status', 'Tempo')
                            ->orWhere('metode_bayar', 'TEMPO');
                    })
                    ->sum('sisa_piutang');
            } else {
                $piutangBerjalan = (float) DB::table('tbl_penjualan')
                    ->where(function ($query) {
                        $query->where('status', 'Tempo')
                            ->orWhere('metode_bayar', 'TEMPO');
                    })
                    ->sum(DB::raw($netSalesExpression));
            }

            $transaksiTerbaru = DB::table('tbl_penjualan')
                ->select([
                    'id',
                    'no_invoice',
                    'tgl_transaksi',
                    'tipe_pelanggan',
                    'nama_pelanggan',
                    DB::raw($netSalesExpression . ' as total_belanja'),
                    'refund_total',
                    'metode_bayar',
                    'status',
                    'cashier_name',
                    'created_at',
                ])
                ->orderByDesc('tgl_transaksi')
                ->orderByDesc('id')
                ->limit(5)
                ->get();

            $salesRows = DB::table('tbl_penjualan')
                ->select(['tgl_transaksi', 'total_belanja', 'refund_total', 'id'])
                ->whereBetween('tgl_transaksi', [$startDate->toDateTimeString(), $endDate->toDateTimeString()])
                ->get()
                ->groupBy(function ($row) {
                    return Carbon::parse($row->tgl_transaksi)->toDateString();
                });

            foreach (CarbonPeriod::create($startDate->copy()->startOfDay(), $endDate->copy()->startOfDay()) as $date) {
                $key = $date->toDateString();
                $rows = $salesRows->get($key, collect());
                $chartDataLabels[] = $filter === 'bulanan' ? $date->format('d M') : $date->locale('id')->isoFormat('ddd');
                $chartDataValues[] = (float) $rows->sum(function ($row) {
                    return max(0, (float) ($row->total_belanja ?? 0) - (float) ($row->refund_total ?? 0));
                });
                $chartNotaValues[] = (int) $rows->count();
            }
        } else {
            foreach (CarbonPeriod::create($startDate->copy()->startOfDay(), $endDate->copy()->startOfDay()) as $date) {
                $chartDataLabels[] = $filter === 'bulanan' ? $date->format('d M') : $date->locale('id')->isoFormat('ddd');
                $chartDataValues[] = 0;
                $chartNotaValues[] = 0;
            }
        }

        $stokKritis = collect();
        if (Schema::hasTable('tbl_barang')) {
            $stokQuery = DB::table('tbl_barang')
                ->select([
                    'id',
                    'kode_barang',
                    'nama_barang',
                    'satuan',
                    'sisa_stok',
                    'limit_minimum_stok',
                    'harga_beli_terakhir',
                    'updated_at',
                ]);

            if (Schema::hasColumn('tbl_barang', 'limit_minimum_stok')) {
                $stokQuery->whereRaw('COALESCE(sisa_stok, 0) <= COALESCE(limit_minimum_stok, 0)');
            }

            $stokKritis = $stokQuery
                ->orderBy('sisa_stok')
                ->orderBy('nama_barang')
                ->limit(5)
                ->get();
        }

        $userAktif = optional(Auth::user())->nama_lengkap ?? optional(Auth::user())->username ?? 'Admin';

        return view('dashboard', [
            'filter' => $filter,
            'total_omset' => $totalOmset,
            'piutang_berjalan' => $piutangBerjalan,
            'nota_terbit' => $notaTerbit,
            'user_aktif' => $userAktif,
            'stok_kritis' => $stokKritis,
            'transaksi_terbaru' => $transaksiTerbaru,
            'chartDataLabels' => $chartDataLabels,
            'chartDataValues' => $chartDataValues,
            'chartNotaValues' => $chartNotaValues,
        ]);
    }
}
