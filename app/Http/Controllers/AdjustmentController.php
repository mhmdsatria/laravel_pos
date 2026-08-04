<?php

namespace App\Http\Controllers;

use App\Models\Adjustment;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdjustmentController extends Controller
{
    public function index(Request $request): View
    {
        
        $search = trim((string) $request->query('search', ''));
$keyword = trim((string) $request->query('search', ''));

        $adjustmentQuery = Adjustment::query()
            ->with('product')
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery->where('kode_barang', 'like', '%' . $keyword . '%')
                        ->orWhere('staf_gudang', 'like', '%' . $keyword . '%')
                        ->orWhere('diverifikasi_oleh', 'like', '%' . $keyword . '%')
                        ->orWhere('status', 'like', '%' . $keyword . '%')
                        ->orWhereHas('product', function ($productQuery) use ($keyword) {
                            $productQuery->where('nama_barang', 'like', '%' . $keyword . '%');
                        });
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $adjustments = $adjustmentQuery->paginate(10)->withQueryString();

        $products = Product::query()
            ->orderBy('nama_barang')
            ->get(['kode_barang', 'nama_barang', 'satuan', 'sisa_stok', 'harga_beli_terakhir']);

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $totalOpname = Adjustment::query()
            ->whereBetween('tgl_adjustment', [$startOfMonth, $endOfMonth])
            ->count();

        $pendingSync = Adjustment::query()
            ->where('status', 'Pending')
            ->count();

        $stockShrinkage = Adjustment::query()
            ->leftJoin('tbl_barang', 'tbl_adjustment.kode_barang', '=', 'tbl_barang.kode_barang')
            ->where('tbl_adjustment.selisih', '<', 0)
            ->selectRaw('COALESCE(SUM(ABS(tbl_adjustment.selisih) * COALESCE(tbl_barang.harga_beli_terakhir, 0)), 0) as total_shrinkage')
            ->value('total_shrinkage');

        $totalRows = Adjustment::query()->count();
        $syncedRows = Adjustment::query()->where('status', 'Synced')->count();
        $accuracyRate = $totalRows > 0 ? round(($syncedRows / $totalRows) * 100, 1) : 100;

        $canSyncAdjustment = $this->currentUserCanSyncAdjustment();
        $nextPendingSync = $pendingSync;

        return view('pages.adjustment', compact(
            'adjustments',
            'products',
            'totalOpname',
            'pendingSync',
            'stockShrinkage',
            'accuracyRate',
            'canSyncAdjustment',
            'nextPendingSync', 'search'));
    }

    public function storeDraft(Request $request): RedirectResponse
    {
        if ($request->has('stok_fisik')) {
            $request->merge([
                'stok_fisik' => str_replace(',', '.', (string) $request->input('stok_fisik')),
            ]);
        }

        $validated = $request->validate([
            'kode_barang' => ['required', 'string', 'exists:tbl_barang,kode_barang'],
            'stok_fisik' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['required', 'string', 'max:5000'],
        ]);

        $product = Product::query()
            ->where('kode_barang', $validated['kode_barang'])
            ->firstOrFail();

        $stokSistem = to_float($product->sisa_stok);
        $stokFisik = to_float($validated['stok_fisik']);

        Adjustment::query()->create([
            'tgl_adjustment' => Carbon::now(),
            'kode_barang' => $product->kode_barang,
            'stok_sistem' => $stokSistem,
            'stok_fisik' => $stokFisik,
            'selisih' => $stokFisik - $stokSistem,
            'keterangan' => $validated['keterangan'],
            'staf_gudang' => $this->currentUserName(),
            'diverifikasi_oleh' => null,
            'status' => 'Pending',
        ]);

        return redirect()
            ->route('adjustment.index')
            ->with('success', 'Draft adjustment berhasil disimpan. Data masih menunggu sinkronisasi supervisor.');
    }

    public function syncStock(Request $request, int $id): RedirectResponse
    {
        if (! $this->currentUserCanSyncAdjustment()) {
            return redirect()
                ->route('adjustment.index')
                ->with('error', 'Akun Anda tidak memiliki izin untuk sinkronisasi stok.');
        }

        DB::beginTransaction();

        try {
            $adjustment = Adjustment::query()
                ->where('id', $id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($adjustment->status !== 'Pending') {
                DB::rollBack();

                return redirect()
                    ->route('adjustment.index')
                    ->with('error', 'Adjustment ini sudah pernah disinkronkan.');
            }

            $this->applyAdjustmentToStock($adjustment);

            DB::commit();

            return redirect()
                ->route('adjustment.index')
                ->with('success', 'Stok berhasil disinkronkan untuk item ' . $adjustment->kode_barang . '.');
        } catch (\Throwable $exception) {
            DB::rollBack();

            return redirect()
                ->route('adjustment.index')
                ->with('error', 'Gagal sinkronisasi stok: ' . $exception->getMessage());
        }
    }

    public function syncAllStock(Request $request): RedirectResponse
    {
        if (! $this->currentUserCanSyncAdjustment()) {
            return redirect()
                ->route('adjustment.index')
                ->with('error', 'Akun Anda tidak memiliki izin untuk sinkronisasi stok all.');
        }

        DB::beginTransaction();

        try {
            $pendingAdjustments = Adjustment::query()
                ->where('status', 'Pending')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($pendingAdjustments->isEmpty()) {
                DB::rollBack();

                return redirect()
                    ->route('adjustment.index')
                    ->with('info', 'Tidak ada data adjustment Pending yang perlu disinkronkan.');
            }

            $syncedCount = 0;
            $skippedCount = 0;

            foreach ($pendingAdjustments as $adjustment) {
                $productExists = Product::query()
                    ->where('kode_barang', $adjustment->kode_barang)
                    ->exists();

                if (! $productExists) {
                    $skippedCount++;
                    continue;
                }

                $this->applyAdjustmentToStock($adjustment);
                $syncedCount++;
            }

            DB::commit();

            $message = 'Sinkronisasi stok all selesai. Berhasil: ' . $syncedCount . ' item.';

            if ($skippedCount > 0) {
                $message .= ' Dilewati karena produk tidak ditemukan: ' . $skippedCount . ' item.';
            }

            return redirect()
                ->route('adjustment.index')
                ->with('success', $message);
        } catch (\Throwable $exception) {
            DB::rollBack();

            return redirect()
                ->route('adjustment.index')
                ->with('error', 'Gagal sinkronisasi stok all: ' . $exception->getMessage());
        }
    }

    private function applyAdjustmentToStock(Adjustment $adjustment): void
    {
        $product = Product::query()
            ->where('kode_barang', $adjustment->kode_barang)
            ->lockForUpdate()
            ->firstOrFail();

        $stokFisik = to_float($adjustment->stok_fisik);
        $now = Carbon::now();

        $product->sisa_stok = $stokFisik;
        $product->save();

        if (Schema::hasTable('stok_barang')) {
            $stockPayload = [
                'nama_barang' => $product->nama_barang,
                'stok_aktif' => $stokFisik,
            ];

            if (Schema::hasColumn('stok_barang', 'updated_at')) {
                $stockPayload['updated_at'] = $now;
            }

            if (Schema::hasColumn('stok_barang', 'created_at')) {
                $stockPayload['created_at'] = $now;
            }

            DB::table('stok_barang')->updateOrInsert(
                ['kode_barang' => $product->kode_barang],
                $stockPayload
            );
        }

        $adjustment->status = 'Synced';
        $adjustment->diverifikasi_oleh = $this->currentUserName();
        $adjustment->save();
    }

    private function currentUserName(): string
    {
        $user = Auth::user();

        if (! $user) {
            return 'System';
        }

        return (string) ($user->nama_lengkap ?? $user->name ?? $user->username ?? 'System');
    }

    private function currentUserCanSyncAdjustment(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        $roleValue = strtolower((string) ($user->role ?? $user->level ?? $user->jabatan ?? $user->tipe_user ?? ''));

        if ($roleValue === '') {
            return true;
        }

        return in_array($roleValue, ['owner', 'supervisor', 'admin', 'administrator', 'manager'], true);
    }
}
