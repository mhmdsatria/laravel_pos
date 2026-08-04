<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $customers = Customer::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('nama_pelanggan', 'like', '%' . $search . '%')
                        ->orWhere('kode_cuts', 'like', '%' . $search . '%')
                        ->orWhere('no_whatsapp', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $nextKodeCuts = Customer::generateNextKodeCuts();

        return view('pages.customer', compact('customers', 'search', 'nextKodeCuts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_pelanggan' => ['required', 'string', 'max:150'],
            'no_whatsapp' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'string', 'max:150'],
            'kategori' => ['required', 'in:Toko,Normal,Sales'],
            'alamat_lengkap' => ['nullable', 'string', 'max:2000'],
        ], [
            'nama_pelanggan.required' => 'Nama pelanggan wajib diisi.',
            'no_whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'kategori.required' => 'Kategori pelanggan wajib dipilih.',
            'kategori.in' => 'Kategori pelanggan tidak valid.',
        ]);

        Customer::create($validated);

        return redirect()
            ->route('customer.index')
            ->with('success', 'Data pelanggan berhasil disimpan.');
    }

    public function show(string $id): JsonResponse
    {
        $customer = Customer::query()->findOrFail($id);

        return response()->json([
            'id' => $customer->id,
            'kode_cuts' => $customer->kode_cuts,
            'nama_pelanggan' => $customer->nama_pelanggan,
            'no_whatsapp' => $customer->no_whatsapp,
            'email' => $customer->email,
            'kategori' => $customer->kategori,
            'alamat_lengkap' => $customer->alamat_lengkap,
            'created_at' => optional($customer->created_at)->format('d/m/Y H:i'),
            'updated_at' => optional($customer->updated_at)->format('d/m/Y H:i'),
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $customer = Customer::query()->findOrFail($id);

        $validated = $request->validate([
            'nama_pelanggan' => ['required', 'string', 'max:150'],
            'no_whatsapp' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'string', 'max:150'],
            'kategori' => ['required', 'in:Toko,Normal,Sales'],
            'alamat_lengkap' => ['nullable', 'string', 'max:2000'],
        ], [
            'nama_pelanggan.required' => 'Nama pelanggan wajib diisi.',
            'no_whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'kategori.required' => 'Kategori pelanggan wajib dipilih.',
            'kategori.in' => 'Kategori pelanggan tidak valid.',
        ]);

        $customer->update($validated);

        return redirect()
            ->route('customer.index')
            ->with('success', 'Data pelanggan berhasil diperbarui.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $customer = Customer::query()->findOrFail($id);
        $customer->delete();

        return redirect()
            ->route('customer.index')
            ->with('success', 'Data pelanggan berhasil dihapus.');
    }
}
