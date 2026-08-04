@extends('layouts.app')

@section('title', 'Master Management - Toko Bangunan 39')

@section('content')
<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e7ff; border-radius: 10px; }
    .tab-active { color: #006c49; border-bottom: 2px solid #006c49; font-weight: bold; }
    body.modal-open { overflow: hidden; }
</style>

<div class="w-full space-y-lg px-1 text-on-surface">
    <div class="flex flex-col gap-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="uppercase text-primary font-headline-xl text-headline-xl text-on-surface">Master Management</h1>
                <p class="font-body-md text-body-md text-on-surface-variant mt-1">Kelola direktori supplier material dan data sales lapangan dalam satu pusat data.</p>
            </div>
        </div>
        <!-- CONTAINER UTAMA TABS & KONTROL (Sejajar & Rata Kanan pada Desktop) -->
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between border-b border-outline-variant w-full gap-4 pb-2 lg:pb-0">
    
    <!-- KIRI: Tombol Navigasi Tab -->
    <div class="flex items-center shrink-0">
        <button class="px-6 py-3 font-label-md text-label-md transition-colors cursor-pointer {{ $activeTab === 'supplier' ? 'tab-active' : 'text-on-surface-variant hover:text-primary' }}" id="tabSupplierBtn" onclick="switchTab('supplier')" type="button">Data Supplier</button>
        <button class="px-6 py-3 font-label-md text-label-md transition-colors cursor-pointer {{ $activeTab === 'sales' ? 'tab-active' : 'text-on-surface-variant hover:text-primary' }}" id="tabSalesBtn" onclick="switchTab('sales')" type="button">Data Sales Lapangan</button>
    </div>

    <!-- KANAN: Group Pencarian & Tombol Aksi (Otomatis Rata Kanan) -->
    <div class="flex items-center justify-end gap-3 flex-1 w-full lg:w-auto lg:mb-2">
        
        <!-- KONTROL TAB SUPPLIER -->
        <div id="controlsSupplier" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto {{ $activeTab === 'supplier' ? '' : 'hidden' }}">
            <form method="GET" action="{{ route('master-management.index') }}" id="supplierSearchForm" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                <input type="hidden" name="tab" value="supplier">
                <div class="relative w-full sm:w-64">
                    <span class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 z-10 -translate-y-1/2 text-on-surface-variant" style="font-size:20px;line-height:20px;">
                        search
                    </span>
                    <input
                        name="search"
                        value="{{ $activeTab === 'supplier' ? $search : '' }}"
                        placeholder="Cari nama atau PIC..."
                        type="text"
                        autocomplete="off"
                        class="block h-11 w-full rounded-full border border-outline-variant bg-surface-container-low pl-12 pr-4 text-body-md text-on-surface outline-none transition-all placeholder:text-on-surface-variant/60 focus:border-primary focus:ring-4 focus:ring-primary/10"
                    >
                </div>

            </form>
            
            <button class="flex items-center justify-center gap-2 px-5 h-11 bg-primary text-on-primary font-label-md text-label-md rounded-xl hover:brightness-110 active:scale-95 transition-all shadow-md shrink-0 cursor-pointer" onclick="openCreateSupplierModal()" type="button">
                <span class="material-symbols-outlined">add</span>
                Tambah Supplier
            </button>
        </div>

        <!-- KONTROL TAB SALES -->
        <div id="controlsSales" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto {{ $activeTab === 'sales' ? '' : 'hidden' }}">
            <form method="GET" action="{{ route('master-management.index') }}" id="salesSearchForm" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                <input type="hidden" name="tab" value="sales">
                <div class="relative w-full sm:w-64">
                    <span class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 z-10 -translate-y-1/2 text-on-surface-variant" style="font-size:20px;line-height:20px;">
                        search
                    </span>
                    <input
                        name="search"
                        value="{{ $activeTab === 'sales' ? $search : '' }}"
                        placeholder="Cari kode atau nama sales..."
                        type="text"
                        autocomplete="off"
                        class="block h-11 w-full rounded-full border border-outline-variant bg-surface-container-low pl-12 pr-4 text-body-md text-on-surface outline-none transition-all placeholder:text-on-surface-variant/60 focus:border-primary focus:ring-4 focus:ring-primary/10"
                    >
                </div>
            </form>
            
            <button class="flex items-center justify-center gap-2 px-5 h-11 bg-primary text-on-primary font-label-md text-label-md rounded-xl hover:brightness-110 active:scale-95 transition-all shadow-md shrink-0 cursor-pointer" onclick="openCreateSalesModal()" type="button">
                <span class="material-symbols-outlined">add</span>
                Tambah Sales
            </button>
        </div>

    </div>
</div>
    </div>

    <div class="space-y-md {{ $activeTab === 'supplier' ? '' : 'hidden' }}" id="sectionSupplier">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-3 w-full mb-4">
        </div>

        <div class="bg-white rounded-xl border border-outline-variant overflow-hidden shadow-sm">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse" id="supplierTable">
                    <thead class="bg-surface-container-low">
                        <tr>
                            <th class="px-lg py-3 font-label-md text-label-md text-on-surface-variant">No</th>
                            <th class="px-lg py-3 font-label-md text-label-md text-on-surface-variant">Nama PT/Distributor</th>
                            <th class="px-lg py-3 font-label-md text-label-md text-on-surface-variant">Alamat</th>
                            <th class="px-lg py-3 font-label-md text-label-md text-on-surface-variant">Nama PIC</th>
                            <th class="px-lg py-3 font-label-md text-label-md text-on-surface-variant">Nomor HP</th>
                            <th class="px-lg py-3 font-label-md text-label-md text-on-surface-variant text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @forelse($suppliers as $key => $sup)
                            <tr class="hover:bg-surface-container-low transition-colors group">
                                <td class="px-lg py-4 font-body-md text-body-md text-on-surface-variant">{{ $suppliers->firstItem() + $key }}</td>
                                <td class="px-lg py-4 font-body-md text-body-md font-bold text-on-surface">{{ $sup->nama_supplier }}</td>
                                <td class="px-lg py-4 font-body-md text-body-md text-on-surface-variant max-w-xs truncate" title="{{ $sup->alamat }}">{{ $sup->alamat }}</td>
                                <td class="px-lg py-4 font-body-md text-body-md text-on-surface">{{ $sup->nama_pic }}</td>
                                <td class="px-lg py-4 font-body-md text-body-md text-on-surface">{{ $sup->no_hp }}</td>
                                <td class="px-lg py-4 text-right">
                                    <div class="flex justify-end items-center gap-2">
                                        <button class="text-on-surface-variant hover:text-primary transition-colors p-1 rounded-full hover:bg-surface-container-high" title="Edit Supplier" type="button" data-fetch-url="{{ route('master-management.supplier.show', $sup->id) }}" data-update-url="{{ route('master-management.supplier.update', $sup->id) }}" onclick="openEditSupplierModal(this)">
                                            <span class="material-symbols-outlined">edit</span>
                                        </button>
                                        <form method="POST" action="{{ route('master-management.supplier.destroy', $sup->id) }}" class="inline delete-form" data-label="supplier {{ $sup->nama_supplier }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-on-surface-variant hover:text-error transition-colors p-1 rounded-full hover:bg-error-container" title="Hapus Supplier" type="submit">
                                                <span class="material-symbols-outlined">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-lg py-10 text-center text-on-surface-variant font-body-md text-body-md">
                                    Belum ada data supplier. Klik tombol Tambah Supplier untuk membuat data pertama.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-lg py-4 border-t border-outline-variant bg-surface-container-lowest">
                {{ $suppliers->links() }}
            </div>
        </div>
    </div>

    <div class="space-y-md {{ $activeTab === 'sales' ? '' : 'hidden' }}" id="sectionSales">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-3 w-full mb-4">
        </div>

        <div class="bg-white rounded-xl border border-outline-variant overflow-hidden shadow-sm">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse" id="salesTable">
                    <thead class="bg-surface-container-low">
                        <tr>
                            <th class="px-lg py-3 font-label-md text-label-md text-on-surface-variant">No</th>
                            <th class="px-lg py-3 font-label-md text-label-md text-on-surface-variant">Kode Sales</th>
                            <th class="px-lg py-3 font-label-md text-label-md text-on-surface-variant">Nama Lengkap</th>
                            <th class="px-lg py-3 font-label-md text-label-md text-on-surface-variant">Status</th>
                            <th class="px-lg py-3 font-label-md text-label-md text-on-surface-variant text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @forelse($sales as $key => $sls)
                            <tr class="hover:bg-surface-container-low transition-colors group">
                                <td class="px-lg py-4 font-body-md text-body-md text-on-surface-variant">{{ $sales->firstItem() + $key }}</td>
                                <td class="px-lg py-4 font-body-md text-body-md font-bold text-primary">{{ $sls->kode_sales }}</td>
                                <td class="px-lg py-4 font-body-md text-body-md text-on-surface font-semibold">{{ $sls->nama_sales }}</td>
                                <td class="px-lg py-4">
                                    @if($sls->status === 'Aktif')
                                        <span class="px-2 py-1 bg-primary-container/20 text-primary text-xs font-bold rounded">Aktif</span>
                                    @else
                                        <span class="px-2 py-1 bg-error-container text-error text-xs font-bold rounded">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="px-lg py-4 text-right">
                                    <div class="flex justify-end items-center gap-2">
                                        <button class="text-on-surface-variant hover:text-primary transition-colors p-1 rounded-full hover:bg-surface-container-high" title="Edit Sales" type="button" data-fetch-url="{{ route('master-management.sales.show', $sls->id) }}" data-update-url="{{ route('master-management.sales.update', $sls->id) }}" onclick="openEditSalesModal(this)">
                                            <span class="material-symbols-outlined">edit</span>
                                        </button>
                                        <form method="POST" action="{{ route('master-management.sales.destroy', $sls->id) }}" class="inline delete-form" data-label="sales {{ $sls->nama_sales }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-on-surface-variant hover:text-error transition-colors p-1 rounded-full hover:bg-error-container" title="Hapus Sales" type="submit">
                                                <span class="material-symbols-outlined">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-lg py-10 text-center text-on-surface-variant font-body-md text-body-md">
                                    Belum ada data sales. Klik tombol Tambah Sales untuk membuat data pertama.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-lg py-4 border-t border-outline-variant bg-surface-container-lowest">
                {{ $sales->links() }}
            </div>
        </div>
    </div>
</div>

<div class="fixed inset-0 z-[100] hidden items-center justify-center p-4" id="modalSupplier">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-md" onclick="toggleModal('modalSupplier', false)"></div>
    <div class="relative w-full max-w-[520px] bg-white rounded-xl shadow-2xl overflow-hidden border border-outline-variant transform transition-all duration-300">
        <div class="flex items-center justify-between px-lg py-md border-b border-outline-variant">
            <h3 class="font-headline-md text-headline-md text-on-surface" id="titleModalSupplier">Tambah Supplier Baru</h3>
            <button class="p-2 hover:bg-surface-container-high rounded-full" onclick="toggleModal('modalSupplier', false)" type="button"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form id="formSupplier" method="POST" action="{{ route('master-management.supplier.store') }}" data-form-type="supplier">
            @csrf
            <div id="supplierMethodContainer"></div>
            <div class="p-lg space-y-md">
                <div class="space-y-sm">
                    <label class="block font-label-md text-label-md text-on-surface-variant">Nama PT/Distributor</label>
                    <input class="w-full bg-white border border-outline-variant rounded-lg px-4 py-2.5 font-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" name="nama_supplier" id="nama_supplier" placeholder="Masukkan nama perusahaan" required type="text"/>
                </div>
                <div class="space-y-sm">
                    <label class="block font-label-md text-label-md text-on-surface-variant">Alamat</label>
                    <textarea class="w-full bg-white border border-outline-variant rounded-lg px-4 py-2.5 font-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none resize-none" name="alamat" id="alamat" placeholder="Alamat lengkap operasional..." rows="3" required></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                    <div class="space-y-sm">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Nama PIC</label>
                        <input class="w-full bg-white border border-outline-variant rounded-lg px-4 py-2.5 font-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" name="nama_pic" id="nama_pic" placeholder="Nama Penanggung Jawab" required type="text"/>
                    </div>
                    <div class="space-y-sm">
                        <label class="block font-label-md text-label-md text-on-surface-variant">Nomor HP</label>
                        <input class="w-full bg-white border border-outline-variant rounded-lg px-4 py-2.5 font-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" name="no_hp" id="no_hp" placeholder="08xx-xxxx-xxxx" required type="tel"/>
                    </div>
                </div>
            </div>
            <div class="px-lg py-md bg-surface-container-low flex items-center justify-end gap-3">
                <button class="px-6 py-2.5 font-label-md text-label-md text-on-surface-variant hover:bg-surface-container-high rounded-lg" onclick="toggleModal('modalSupplier', false)" type="button">Batal</button>
                <button class="px-8 py-2.5 bg-primary text-on-primary font-label-md text-label-md rounded-lg shadow hover:brightness-110 active:scale-95 transition-all" type="submit">Simpan Supplier</button>
            </div>
        </form>
    </div>
</div>

<div class="fixed inset-0 z-[100] hidden items-center justify-center p-4" id="modalSales">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-md" onclick="toggleModal('modalSales', false)"></div>
    <div class="relative w-full max-w-[440px] bg-white rounded-xl shadow-2xl overflow-hidden border border-outline-variant transform transition-all duration-300">
        <div class="flex items-center justify-between px-lg py-md border-b border-outline-variant">
            <h3 class="font-headline-md text-headline-md text-on-surface" id="titleModalSales">Tambah Sales Baru</h3>
            <button class="p-2 hover:bg-surface-container-high rounded-full" onclick="toggleModal('modalSales', false)" type="button"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form id="formSales" method="POST" action="{{ route('master-management.sales.store') }}" data-form-type="sales">
            @csrf
            <div id="salesMethodContainer"></div>
            <div class="p-lg space-y-md">
                <div class="space-y-sm">
                    <label class="block font-label-md text-label-md text-on-surface-variant">Kode Sales</label>
                    <input class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-4 py-2.5 font-body-md text-on-surface-variant outline-none cursor-not-allowed" id="kode_sales" readonly type="text" value="{{ $nextKodeSales }}"/>
                    <p class="text-[10px] text-on-surface-variant italic">Kode di-generate otomatis oleh sistem.</p>
                </div>
                <div class="space-y-sm">
                    <label class="block font-label-md text-label-md text-on-surface-variant">Nama Lengkap Sales</label>
                    <input class="w-full bg-white border border-outline-variant rounded-lg px-4 py-2.5 font-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" name="nama_sales" id="nama_sales" placeholder="Masukkan nama lengkap" required type="text"/>
                </div>
                <div class="space-y-sm">
                    <label class="block font-label-md text-label-md text-on-surface-variant">Status</label>
                    <select class="w-full bg-white border border-outline-variant rounded-lg px-4 py-2.5 font-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none" name="status" id="status" required>
                        <option value="Aktif">Aktif</option>
                        <option value="Nonaktif">Nonaktif</option>
                    </select>
                </div>
            </div>
            <div class="px-lg py-md bg-surface-container-low flex items-center justify-end gap-3">
                <button class="px-6 py-2.5 font-label-md text-label-md text-on-surface-variant hover:bg-surface-container-high rounded-lg" onclick="toggleModal('modalSales', false)" type="button">Batal</button>
                <button class="px-8 py-2.5 bg-primary text-on-primary font-label-md text-label-md rounded-lg shadow hover:brightness-110 active:scale-95 transition-all" type="submit">Simpan Data Sales</button>
            </div>
        </form>
    </div>
</div>

<div class="fixed inset-0 z-[120] hidden items-center justify-center p-4" id="confirmSubmitModal">
    <div class="absolute inset-0 backdrop-blur-xl bg-black/60"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white border border-outline-variant shadow-2xl overflow-hidden">
        <div class="p-lg border-b border-outline-variant flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center">
                <span class="material-symbols-outlined">verified</span>
            </div>
            <div>
                <h3 class="font-headline-md text-headline-md text-on-surface">Konfirmasi Eksekusi Data</h3>
                <p class="font-body-md text-body-md text-on-surface-variant">Validasi lapis kedua sebelum masuk database.</p>
            </div>
        </div>
        <div class="p-lg space-y-md">
            <p class="font-body-lg text-body-lg text-on-surface" id="confirmSubmitText">Apakah Anda yakin ingin menyimpan data ini?</p>
            <div class="rounded-xl bg-surface-container-low border border-outline-variant p-md text-on-surface-variant font-body-md text-body-md" id="confirmSubmitDetail">Data akan ditulis ke database MySQL toko.</div>
        </div>
        <div class="px-lg py-md bg-surface-container-low flex justify-end gap-3">
            <button class="px-5 py-2.5 rounded-lg text-on-surface-variant hover:bg-surface-container-high font-label-md text-label-md" type="button" onclick="closeSubmitConfirmation()">Periksa Lagi</button>
            <button class="px-6 py-2.5 rounded-lg bg-primary text-on-primary font-label-md text-label-md shadow hover:brightness-110 active:scale-95" type="button" onclick="executePendingFormSubmit()">Ya, Kirim</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let pendingSubmitForm = null;

    // Ganti fungsi switchTab lama Anda dengan ini:
window.switchTab = function(tab) {
    const sectionSupplier = document.getElementById('sectionSupplier');
    const sectionSales = document.getElementById('sectionSales');
    const tabSupplierBtn = document.getElementById('tabSupplierBtn');
    const tabSalesBtn = document.getElementById('tabSalesBtn');
    
    // Selector untuk kontrol tombol kanan
    const controlsSupplier = document.getElementById('controlsSupplier');
    const controlsSales = document.getElementById('controlsSales');

    if (tab === 'supplier') {
        // Tampilkan konten Supplier, sembunyikan Sales
        if (sectionSupplier) sectionSupplier.classList.remove('hidden');
        if (sectionSales) sectionSales.classList.add('hidden');
        
        // SINKRONISASI TOMBOL KANAN: Tampilkan Supplier, sembunyikan Sales
        if (controlsSupplier) controlsSupplier.classList.remove('hidden');
        if (controlsSales) controlsSales.classList.add('hidden');

        if (tabSupplierBtn) {
            tabSupplierBtn.classList.add('tab-active');
            tabSupplierBtn.classList.remove('text-on-surface-variant');
        }
        if (tabSalesBtn) {
            tabSalesBtn.classList.remove('tab-active');
            tabSalesBtn.classList.add('text-on-surface-variant');
        }
        window.history.replaceState(null, '', updateQueryStringParameter(window.location.href, 'tab', 'supplier'));
    } else {
        // Tampilkan konten Sales, sembunyikan Supplier
        if (sectionSupplier) sectionSupplier.classList.add('hidden');
        if (sectionSales) sectionSales.classList.remove('hidden');

        // SINKRONISASI TOMBOL KANAN: Tampilkan Sales, sembunyikan Supplier
        if (controlsSupplier) controlsSupplier.classList.add('hidden');
        if (controlsSales) controlsSales.classList.remove('hidden');

        if (tabSalesBtn) {
            tabSalesBtn.classList.add('tab-active');
            tabSalesBtn.classList.remove('text-on-surface-variant');
        }
        if (tabSupplierBtn) {
            tabSupplierBtn.classList.remove('tab-active');
            tabSupplierBtn.classList.add('text-on-surface-variant');
        }
        window.history.replaceState(null, '', updateQueryStringParameter(window.location.href, 'tab', 'sales'));
    }
}

    function updateQueryStringParameter(uri, key, value) {
        const url = new URL(uri, window.location.origin);
        url.searchParams.set(key, value);
        return url.pathname + url.search;
    }

    function toggleModal(id, show) {
        const modal = document.getElementById(id);
        const body = document.body;

        if (!modal) return;

        if (show) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            body.classList.add('modal-open');
        } else {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            body.classList.remove('modal-open');
        }
    }

    function setFormLoading(form, isLoading) {
        const inputs = form.querySelectorAll('input, textarea, select, button');
        inputs.forEach(function (input) {
            input.disabled = isLoading;
        });
    }

    function openCreateSupplierModal() {
        const form = document.getElementById('formSupplier');
        document.getElementById('titleModalSupplier').textContent = 'Tambah Supplier Baru';
        document.getElementById('supplierMethodContainer').innerHTML = '';
        form.action = '{{ route('master-management.supplier.store') }}';
        form.reset();
        toggleModal('modalSupplier', true);
    }

    async function openEditSupplierModal(button) {
        const form = document.getElementById('formSupplier');
        const fetchUrl = button.getAttribute('data-fetch-url');
        const updateUrl = button.getAttribute('data-update-url');

        document.getElementById('titleModalSupplier').textContent = 'Edit Data Supplier';
        document.getElementById('supplierMethodContainer').innerHTML = '<input type="hidden" name="_method" value="PUT">';
        form.action = updateUrl;
        form.reset();
        toggleModal('modalSupplier', true);
        setFormLoading(form, true);

        try {
            const response = await fetch(fetchUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Gagal memuat data supplier.');
            }

            const data = await response.json();
            document.getElementById('nama_supplier').value = data.nama_supplier || '';
            document.getElementById('alamat').value = data.alamat || '';
            document.getElementById('nama_pic').value = data.nama_pic || '';
            document.getElementById('no_hp').value = data.no_hp || '';
        } catch (error) {
            alert(error.message);
            toggleModal('modalSupplier', false);
        } finally {
            setFormLoading(form, false);
        }
    }

    function openCreateSalesModal() {
        const form = document.getElementById('formSales');
        document.getElementById('titleModalSales').textContent = 'Tambah Sales Baru';
        document.getElementById('salesMethodContainer').innerHTML = '';
        form.action = '{{ route('master-management.sales.store') }}';
        form.reset();
        document.getElementById('kode_sales').value = '{{ $nextKodeSales }}';
        document.getElementById('status').value = 'Aktif';
        toggleModal('modalSales', true);
    }

    async function openEditSalesModal(button) {
        const form = document.getElementById('formSales');
        const fetchUrl = button.getAttribute('data-fetch-url');
        const updateUrl = button.getAttribute('data-update-url');

        document.getElementById('titleModalSales').textContent = 'Edit Data Sales';
        document.getElementById('salesMethodContainer').innerHTML = '<input type="hidden" name="_method" value="PUT">';
        form.action = updateUrl;
        form.reset();
        toggleModal('modalSales', true);
        setFormLoading(form, true);

        try {
            const response = await fetch(fetchUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Gagal memuat data sales.');
            }

            const data = await response.json();
            document.getElementById('kode_sales').value = data.kode_sales || '';
            document.getElementById('nama_sales').value = data.nama_sales || '';
            document.getElementById('status').value = data.status || 'Aktif';
        } catch (error) {
            alert(error.message);
            toggleModal('modalSales', false);
        } finally {
            setFormLoading(form, false);
        }
    }

    function openSubmitConfirmation(form) {
        pendingSubmitForm = form;
        const formType = form.getAttribute('data-form-type');
        const confirmText = document.getElementById('confirmSubmitText');
        const confirmDetail = document.getElementById('confirmSubmitDetail');

        if (formType === 'supplier') {
            const supplierName = document.getElementById('nama_supplier').value || 'supplier baru';
            const picName = document.getElementById('nama_pic').value || 'PIC belum diisi';
            confirmText.textContent = 'Apakah Anda yakin ingin mengeksekusi data supplier ini ke database MySQL?';
            confirmDetail.textContent = 'Supplier: ' + supplierName + ' | PIC: ' + picName + '. Pastikan nama, alamat, dan nomor kontak sudah benar.';
        } else {
            const salesName = document.getElementById('nama_sales').value || 'sales baru';
            const salesStatus = document.getElementById('status').value || 'Aktif';
            confirmText.textContent = 'Apakah Anda yakin ingin mengeksekusi data sales ini ke database MySQL?';
            confirmDetail.textContent = 'Sales: ' + salesName + ' | Status: ' + salesStatus + '. Pastikan data personil lapangan sudah benar.';
        }

        toggleModal('confirmSubmitModal', true);
    }

    function closeSubmitConfirmation() {
        pendingSubmitForm = null;
        toggleModal('confirmSubmitModal', false);
    }

    function executePendingFormSubmit() {
        if (!pendingSubmitForm) {
            toggleModal('confirmSubmitModal', false);
            return;
        }

        const form = pendingSubmitForm;
        pendingSubmitForm = null;
        form.dataset.confirmedSubmit = 'true';
        form.submit();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const activeTab = '{{ $activeTab }}';
        switchTab(activeTab);

        const supplierForm = document.getElementById('formSupplier');
        const salesForm = document.getElementById('formSales');

        if (supplierForm) {
            supplierForm.addEventListener('submit', function (event) {
                if (supplierForm.dataset.confirmedSubmit === 'true') return;
                event.preventDefault();
                openSubmitConfirmation(supplierForm);
            });
        }

        if (salesForm) {
            salesForm.addEventListener('submit', function (event) {
                if (salesForm.dataset.confirmedSubmit === 'true') return;
                event.preventDefault();
                openSubmitConfirmation(salesForm);
            });
        }

        document.querySelectorAll('.delete-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                const label = form.getAttribute('data-label') || 'data ini';
                const approved = confirm('Hapus ' + label + '? Data yang terhapus tidak bisa dikembalikan.');
                if (!approved) {
                    event.preventDefault();
                }
            });
        });
    });
</script>
@endpush

<script id="MASTER_MANAGEMENT_CLICK_FIX_FALLBACK">
    window.addEventListener('load', function () {
        if (typeof window.toggleModal !== 'function') {
            window.toggleModal = function (id, show) {
                const modal = document.getElementById(id);
                if (!modal) return;
                if (show) {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.classList.add('modal-open');
                } else {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    document.body.classList.remove('modal-open');
                }
            };
        }

        if (typeof window.switchTab !== 'function') {
            window.switchTab = function (tab) {
                const sectionSupplier = document.getElementById('sectionSupplier');
                const sectionSales = document.getElementById('sectionSales');
                const tabSupplierBtn = document.getElementById('tabSupplierBtn');
                const tabSalesBtn = document.getElementById('tabSalesBtn');

                if (!sectionSupplier || !sectionSales || !tabSupplierBtn || !tabSalesBtn) return;

                if (tab === 'supplier') {
                    sectionSupplier.classList.remove('hidden');
                    sectionSales.classList.add('hidden');
                    tabSupplierBtn.classList.add('tab-active');
                    tabSupplierBtn.classList.remove('text-on-surface-variant');
                    tabSalesBtn.classList.remove('tab-active');
                    tabSalesBtn.classList.add('text-on-surface-variant');
                } else {
                    sectionSupplier.classList.add('hidden');
                    sectionSales.classList.remove('hidden');
                    tabSalesBtn.classList.add('tab-active');
                    tabSalesBtn.classList.remove('text-on-surface-variant');
                    tabSupplierBtn.classList.remove('tab-active');
                    tabSupplierBtn.classList.add('text-on-surface-variant');
                }
            };
        }
    });
</script>