@extends('layouts.app')

@section('title', 'Master Pelanggan | Toko Bangunan 39')

@section('content')
{{-- Page Header --}}
<div class="flex justify-between items-center gap-md mb-lg">
    <div>
        <h2 class="font-headline-xl text-headline-xl text-on-surface uppercase text-primary">Master Pelanggan</h2>
        <p class="text-on-surface-variant mt-1">Kelola database toko mitra, pelanggan retail, dan status keagenan sales secara terpusat.</p>
    </div>
    <button
        type="button"
        class="flex items-center gap-2 px-md py-2 bg-primary text-on-primary rounded-lg font-bold shadow-md hover:brightness-110 active:scale-95 transition-all cursor-pointer"
        onclick="openCustomerModal()">
        <span class="material-symbols-outlined">add</span>
        <span>Pelanggan Baru</span>
    </button>
</div>

{{-- Main Container Card --}}
<div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">

    {{-- Search & Filter Header --}}
    <div class="px-lg py-md border-b border-outline-variant flex flex-col md:flex-row md:items-center md:justify-between gap-md bg-surface-container-lowest">
        <form
            method="GET"
            action="{{ route('customer.index') }}"
            class="relative block shrink-0"
            style="width: 420px; max-width: 100%;"
        >
            <span
                class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 z-10 -translate-y-1/2 text-on-surface-variant"
                style="font-size: 20px; line-height: 20px;"
            >
                search
            </span>

            <input
                name="search"
                value="{{ $search }}"
                placeholder="Cari kode, nama pelanggan, atau nomor telepon..."
                type="text"
                autocomplete="off"
                class="block h-11 w-full border border-outline-variant bg-surface-container-low pl-12 pr-4 text-body-md text-on-surface outline-none transition-all placeholder:text-on-surface-variant/60 focus:border-primary focus:ring-4 focus:ring-primary/10"
                style="border-radius: 9999px;"
            >
        </form>

        <p class="shrink-0 text-label-md text-on-surface-variant">
            Total pelanggan: {{ $customers->total() }}
        </p>
    </div>

    {{-- Data Table --}}
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse" id="customerTable">
            <thead>
                <tr class="bg-surface-container-low">
                    <th class="px-lg py-3 font-label-md text-label-md text-on-surface-variant uppercase w-12">No</th>
                    <th class="px-lg py-3 font-label-md text-label-md text-on-surface-variant uppercase w-32">Kode</th>
                    <th class="px-lg py-3 font-label-md text-label-md text-on-surface-variant uppercase">Nama Pelanggan</th>
                    <th class="px-lg py-3 font-label-md text-label-md text-on-surface-variant uppercase text-center w-28">Kategori</th>
                    <th class="px-lg py-3 font-label-md text-label-md text-on-surface-variant uppercase w-40">WhatsApp</th>
                    <th class="px-lg py-3 font-label-md text-label-md text-on-surface-variant uppercase">Alamat Lengkap</th>
                    <th class="px-lg py-3 font-label-md text-label-md text-on-surface-variant uppercase text-center w-28">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                @forelse ($customers as $key => $customer)
                <tr class="hover:bg-surface-container-low transition-colors group">
                    <td class="px-lg py-4 text-body-md">{{ $customers->firstItem() + $key }}</td>
                    <td class="px-lg py-4 text-body-md font-mono text-on-surface-variant">{{ $customer->kode_cuts ?? '-' }}</td>
                    <td class="px-lg py-4 text-body-md font-bold text-primary">{{ $customer->nama_pelanggan }}</td>
                    <td class="px-lg py-4 text-center">
                        @if($customer->kategori === 'Toko')
                        <span class="px-2 py-0.5 rounded-full bg-secondary/10 text-[10px] font-bold text-secondary uppercase tracking-wider">Toko</span>
                        @elseif($customer->kategori === 'Sales')
                        <span class="px-2 py-0.5 rounded-full bg-tertiary/10 text-[10px] font-bold text-tertiary uppercase tracking-wider">Sales</span>
                        @else
                        <span class="px-2 py-0.5 rounded-full bg-surface-container-high text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Normal</span>
                        @endif
                    </td>
                    <td class="px-lg py-4 text-body-md font-medium text-on-surface">{{ $customer->no_whatsapp }}</td>
                    <td class="px-lg py-4 text-body-md text-on-surface-variant max-w-xs truncate" title="{{ $customer->alamat_lengkap }}">
                        {{ $customer->alamat_lengkap ?? '-' }}
                    </td>
                    <td class="px-lg py-4 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <button class="p-1 hover:text-primary transition-colors cursor-pointer" type="button" title="Ubah Data"
                                onclick="openEditCustomerModal({{ json_encode($customer) }})">
                                <span class="material-symbols-outlined text-xl">edit</span>
                            </button>
                            <button class="p-1 hover:text-error transition-colors cursor-pointer" type="button" title="Hapus Pelanggan"
                                onclick="openDeleteConfirmation({{ $customer->id }}, '{{ $customer->nama_pelanggan }}')">
                                <span class="material-symbols-outlined text-xl">delete</span>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-lg py-xl text-center text-on-surface-variant">
                        Belum ada data pelanggan tercatat. Klik <strong>Pelanggan Baru</strong> untuk menambahkan mitra.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination Block --}}
    <div class="px-lg py-md border-t border-outline-variant bg-surface-container-lowest">
        {{ $customers->links() }}
    </div>
</div>
@endsection

@push('modals')
{{-- Modal Form Master Pelanggan (Tambah & Edit) --}}
<div class="fixed inset-0 z-50 backdrop-blur-sm bg-black/40 items-center justify-center hidden" id="customer-modal">
    <div class="bg-white w-full max-w-2xl h-auto rounded-2xl overflow-hidden border border-outline-variant flex flex-col shadow-2xl transition-all duration-300">

        {{-- Modal Header --}}
        <div class="px-lg py-md border-b border-outline-variant flex justify-between items-center bg-surface-container-lowest">
            <div class="flex items-center gap-3 text-primary">
                <span class="material-symbols-outlined text-3xl font-bold" id="modal-icon">person_add</span>
                <h3 class="font-headline-lg text-headline-lg font-bold" id="modal-title">Input Pelanggan Baru</h3>
            </div>
            <button
                class="w-10 h-10 flex items-center justify-center hover:bg-error/10 hover:text-error transition-colors rounded-full cursor-pointer"
                type="button" onclick="closeCustomerModal()">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        {{-- Form Handling --}}
        <form id="customerForm" method="POST" action="{{ route('customer.store') }}" class="p-lg space-y-md bg-surface-container-lowest">
            @csrf
            <div id="method-append"></div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                {{-- Kategori Pelanggan --}}
                <div class="space-y-sm">
                    <label class="block font-label-md text-label-md text-on-surface-variant">Kategori Pelanggan</label>
                    <div class="flex gap-1 rounded-xl bg-surface-container-high p-1" id="kategori-toggle">
                        <input type="hidden" name="kategori" id="kategori_input" value="Normal">

                        <button
                            type="button"
                            data-category="Normal"
                            class="kategori-button flex-1 rounded-lg bg-primary py-2 text-xs font-bold text-on-primary transition-all cursor-pointer"
                            onclick="switchKategori('Normal')"
                            aria-pressed="true"
                        >
                            NORMAL
                        </button>

                        <button
                            type="button"
                            data-category="Toko"
                            class="kategori-button flex-1 rounded-lg bg-transparent py-2 text-xs font-bold text-on-surface-variant transition-all hover:bg-surface-container-highest cursor-pointer"
                            onclick="switchKategori('Toko')"
                            aria-pressed="false"
                        >
                            TOKO
                        </button>
                    </div>
                </div>

                {{-- Kode Pelanggan --}}
                <div class="space-y-sm">
                    <label class="block font-label-md text-label-md text-on-surface-variant">Kode Pelanggan (Berikutnya)</label>
                    <input
                        class="w-full bg-surface-container border border-outline-variant rounded-xl px-4 py-2.5 text-body-md text-on-surface-variant outline-none font-mono"
                        id="kode_cuts_display" name="kode_cuts" value="{{ $nextKodeCuts }}" placeholder="Generates automatically..."
                        type="text" readonly />
                </div>
            </div>

            {{-- Nama Pelanggan --}}
            <div class="space-y-sm">
                <label class="block font-label-md text-label-md text-on-surface-variant">Nama Lengkap / Toko</label>
                <input
                    class="w-full bg-surface border border-outline-variant rounded-xl px-4 py-2.5 text-body-md focus:ring-2 focus:ring-primary outline-none"
                    name="nama_pelanggan" id="nama_pelanggan" placeholder="Masukkan nama pelanggan atau toko..."
                    type="text" required />
            </div>

            {{-- No WhatsApp --}}
            <div class="space-y-sm">
                <label class="block font-label-md text-label-md text-on-surface-variant">Nomor WhatsApp</label>
                <input
                    class="w-full bg-surface border border-outline-variant rounded-xl px-4 py-2.5 text-body-md focus:ring-2 focus:ring-primary outline-none"
                    name="no_whatsapp" id="no_whatsapp" placeholder="Contoh: 081234567xxx" type="text" required />
            </div>

            {{-- Alamat --}}
            <div class="space-y-sm">
                <label class="block font-label-md text-label-md text-on-surface-variant">Alamat Lengkap</label>
                <textarea
                    class="w-full bg-surface border border-outline-variant rounded-xl px-4 py-2.5 text-body-md focus:ring-2 focus:ring-primary outline-none h-24 resize-none"
                    name="alamat_lengkap" id="alamat_lengkap"
                    placeholder="Alamat jalan, nomor rumah, kecamatan, kota..."></textarea>
            </div>

            {{-- Action Buttons --}}
            <div class="border-t border-outline-variant pt-md flex items-center justify-end gap-3">
                <button type="button"
                    class="px-5 py-2.5 rounded-xl font-bold border border-outline-variant hover:bg-surface-container-high active:scale-95 transition-all text-sm text-on-surface-variant cursor-pointer"
                    onclick="closeCustomerModal()">
                    Batal
                </button>
                <button type="submit"
                    class="px-5 py-2.5 bg-primary text-on-primary rounded-xl font-bold shadow-md flex items-center gap-2 hover:brightness-110 active:scale-95 transition-all text-sm cursor-pointer">
                    <span class="material-symbols-outlined text-lg">save</span>
                    <span id="btn-submit-text">Simpan Data</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Konfirmasi Hapus Data --}}
<div class="fixed inset-0 z-[70] hidden items-center justify-center p-4 backdrop-blur-sm bg-black/40" id="confirmDeleteModal">
    <div class="w-full max-w-md rounded-2xl bg-white border border-outline-variant shadow-2xl p-lg">
        <div class="flex items-center gap-sm text-error mb-md">
            <span class="material-symbols-outlined text-3xl">warning</span>
            <h3 class="font-headline-md text-headline-md">Hapus Data Pelanggan</h3>
        </div>
        <p class="text-on-surface-variant mb-lg" id="deleteSummaryText">Apakah Anda yakin ingin menghapus pelanggan ini?</p>

        <form id="deleteForm" method="POST" action="">
            @csrf
            @method('DELETE')
            <div class="flex justify-end gap-sm">
                <button type="button" class="px-lg py-2 rounded-xl border border-outline-variant font-bold cursor-pointer"
                    onclick="hideDeleteModal()">Batal</button>
                <button type="submit"
                    class="px-lg py-2 rounded-xl bg-error text-white font-bold hover:brightness-110 transition-all cursor-pointer">Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>
@endpush

@push('scripts')
<script>
// Penyatuan seluruh konfigurasi & state management tanpa redundansi script
const defaultNextKode = @json($nextKodeCuts);
const customerStoreUrl = @json(route('customer.store'));
const customerBaseUrl = @json(url('/customer'));

function normalizeKategori(value) {
    const category = String(value || '').trim().toLowerCase();
    if (category === 'toko') return 'Toko';
    if (category === 'sales') return 'Sales';
    return 'Normal';
}

window.switchKategori = function (category) {
    const normalized = normalizeKategori(category);
    const input = document.getElementById('kategori_input');
    const buttons = document.querySelectorAll('#kategori-toggle .kategori-button');

    if (input) {
        input.value = normalized;
    }

    buttons.forEach(function (button) {
        const isActive = normalizeKategori(button.dataset.category) === normalized;

        button.classList.remove(
            'bg-primary', 'text-on-primary', 'bg-transparent',
            'text-on-surface-variant', 'hover:bg-surface-container-highest', 'shadow-sm'
        );

        if (isActive) {
            button.classList.add('bg-primary', 'text-on-primary', 'shadow-sm');
        } else {
            button.classList.add('bg-transparent', 'text-on-surface-variant', 'hover:bg-surface-container-highest');
        }
        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
};

function showCustomerModal() {
    const modal = document.getElementById('customer-modal');
    if (!modal) return;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

window.openCustomerModal = function () {
    const form = document.getElementById('customerForm');
    if (!form) return;

    document.getElementById('modal-title').textContent = 'Input Pelanggan Baru';
    document.getElementById('modal-icon').textContent = 'person_add';
    document.getElementById('btn-submit-text').textContent = 'Simpan Data';

    form.action = customerStoreUrl;
    form.reset();

    document.getElementById('method-append').innerHTML = '';
    document.getElementById('kode_cuts_display').value = defaultNextKode;

    window.switchKategori('Normal');
    showCustomerModal();

    setTimeout(function () {
        document.getElementById('nama_pelanggan')?.focus();
    }, 50);
};

window.openEditCustomerModal = function (customer) {
    const form = document.getElementById('customerForm');
    if (!form || !customer) return;

    document.getElementById('modal-title').textContent = 'Ubah Data Pelanggan';
    document.getElementById('modal-icon').textContent = 'edit_note';
    document.getElementById('btn-submit-text').textContent = 'Perbarui Data';

    form.action = `${customerBaseUrl}/${customer.id}`;
    document.getElementById('method-append').innerHTML = '<input type="hidden" name="_method" value="PUT">';

    document.getElementById('kode_cuts_display').value = customer.kode_cuts || '';
    document.getElementById('nama_pelanggan').value = customer.nama_pelanggan || '';
    document.getElementById('no_whatsapp').value = customer.no_whatsapp || '';
    document.getElementById('alamat_lengkap').value = customer.alamat_lengkap || '';

    window.switchKategori(customer.kategori);
    showCustomerModal();
};

window.closeCustomerModal = function () {
    const modal = document.getElementById('customer-modal');
    if (!modal) return;

    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
};

window.openDeleteConfirmation = function (id, name) {
    const modal = document.getElementById('confirmDeleteModal');
    const summary = document.getElementById('deleteSummaryText');
    const form = document.getElementById('deleteForm');

    if (!modal || !summary || !form) return;

    summary.textContent = `Apakah Anda yakin ingin menghapus ${name}? Tindakan ini akan menghapus data secara permanen.`;
    form.action = `${customerBaseUrl}/${id}`;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
};

window.hideDeleteModal = function () {
    const modal = document.getElementById('confirmDeleteModal');
    if (!modal) return;

    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
};

document.addEventListener('DOMContentLoaded', function () {
    window.switchKategori(document.getElementById('kategori_input')?.value || 'Normal');
});

document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;

    const deleteModal = document.getElementById('confirmDeleteModal');
    if (deleteModal && !deleteModal.classList.contains('hidden')) {
        window.hideDeleteModal();
        return;
    }

    const customerModal = document.getElementById('customer-modal');
    if (customerModal && !customerModal.classList.contains('hidden')) {
        window.closeCustomerModal();
    }
});
</script>
@endpush