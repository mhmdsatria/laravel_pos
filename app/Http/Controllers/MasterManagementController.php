<?php

namespace App\Http\Controllers;

use App\Models\Sales;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterManagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $activeTab = $request->query('tab', session('active_master_tab', 'supplier'));
        $activeTab = in_array($activeTab, ['supplier', 'sales'], true) ? $activeTab : 'supplier';

        $supplierQuery = Supplier::query();
        $salesQuery = Sales::query();

        if ($search !== '') {
            $supplierQuery->where(function ($query) use ($search): void {
                $query->where('nama_supplier', 'like', '%' . $search . '%')
                    ->orWhere('alamat', 'like', '%' . $search . '%')
                    ->orWhere('nama_pic', 'like', '%' . $search . '%')
                    ->orWhere('no_hp', 'like', '%' . $search . '%');
            });

            $salesQuery->where(function ($query) use ($search): void {
                $query->where('kode_sales', 'like', '%' . $search . '%')
                    ->orWhere('nama_sales', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%');
            });
        }

        $suppliers = $supplierQuery
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'supplier_page')
            ->withQueryString();

        $sales = $salesQuery
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'sales_page')
            ->withQueryString();

        return view('pages.master_management', [
            'suppliers' => $suppliers,
            'sales' => $sales,
            'search' => $search,
            'activeTab' => $activeTab,
            'nextKodeSales' => Sales::generateNextKodeSales(),
        ]);
    }

    public function showSupplier(int $id): JsonResponse
    {
        $supplier = Supplier::query()->findOrFail($id);

        return response()->json([
            'id' => $supplier->id,
            'nama_supplier' => $supplier->nama_supplier,
            'alamat' => $supplier->alamat,
            'nama_pic' => $supplier->nama_pic,
            'no_hp' => $supplier->no_hp,
            'created_at' => optional($supplier->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($supplier->updated_at)->format('Y-m-d H:i:s'),
        ]);
    }

    public function showSales(int $id): JsonResponse
    {
        $sales = Sales::query()->findOrFail($id);

        return response()->json([
            'id' => $sales->id,
            'kode_sales' => $sales->kode_sales,
            'nama_sales' => $sales->nama_sales,
            'status' => $sales->status,
            'created_at' => optional($sales->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($sales->updated_at)->format('Y-m-d H:i:s'),
        ]);
    }

    public function storeSupplier(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_supplier' => ['required', 'string', 'max:255'],
            'alamat' => ['required', 'string'],
            'nama_pic' => ['required', 'string', 'max:255'],
            'no_hp' => ['required', 'string', 'max:50'],
        ], [
            'nama_supplier.required' => 'Nama supplier wajib diisi.',
            'alamat.required' => 'Alamat supplier wajib diisi.',
            'nama_pic.required' => 'Nama PIC wajib diisi.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
        ]);

        Supplier::query()->create($validated);

        return redirect()
            ->route('master-management.index', ['tab' => 'supplier'])
            ->with('success', 'Data supplier berhasil disimpan.')
            ->with('active_master_tab', 'supplier');
    }

    public function updateSupplier(Request $request, int $id): RedirectResponse
    {
        $supplier = Supplier::query()->findOrFail($id);

        $validated = $request->validate([
            'nama_supplier' => ['required', 'string', 'max:255'],
            'alamat' => ['required', 'string'],
            'nama_pic' => ['required', 'string', 'max:255'],
            'no_hp' => ['required', 'string', 'max:50'],
        ], [
            'nama_supplier.required' => 'Nama supplier wajib diisi.',
            'alamat.required' => 'Alamat supplier wajib diisi.',
            'nama_pic.required' => 'Nama PIC wajib diisi.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
        ]);

        $supplier->update($validated);

        return redirect()
            ->route('master-management.index', ['tab' => 'supplier'])
            ->with('success', 'Data supplier berhasil diperbarui.')
            ->with('active_master_tab', 'supplier');
    }

    public function destroySupplier(int $id): RedirectResponse
    {
        $supplier = Supplier::query()->findOrFail($id);
        $supplier->delete();

        return redirect()
            ->route('master-management.index', ['tab' => 'supplier'])
            ->with('success', 'Data supplier berhasil dihapus.')
            ->with('active_master_tab', 'supplier');
    }

    public function storeSales(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_sales' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
        ], [
            'nama_sales.required' => 'Nama sales wajib diisi.',
            'status.required' => 'Status sales wajib dipilih.',
            'status.in' => 'Status sales tidak valid.',
        ]);

        Sales::query()->create($validated);

        return redirect()
            ->route('master-management.index', ['tab' => 'sales'])
            ->with('success', 'Data sales berhasil disimpan.')
            ->with('active_master_tab', 'sales');
    }

    public function updateSales(Request $request, int $id): RedirectResponse
    {
        $sales = Sales::query()->findOrFail($id);

        $validated = $request->validate([
            'nama_sales' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
        ], [
            'nama_sales.required' => 'Nama sales wajib diisi.',
            'status.required' => 'Status sales wajib dipilih.',
            'status.in' => 'Status sales tidak valid.',
        ]);

        $sales->update($validated);

        return redirect()
            ->route('master-management.index', ['tab' => 'sales'])
            ->with('success', 'Data sales berhasil diperbarui.')
            ->with('active_master_tab', 'sales');
    }

    public function destroySales(int $id): RedirectResponse
    {
        $sales = Sales::query()->findOrFail($id);
        $sales->delete();

        return redirect()
            ->route('master-management.index', ['tab' => 'sales'])
            ->with('success', 'Data sales berhasil dihapus.')
            ->with('active_master_tab', 'sales');
    }
}
