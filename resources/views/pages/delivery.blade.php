@extends('layouts.app')

@section('title', 'Logistik & Surat Jalan | Toko Bangunan 39')
@section('page_title', 'Logistik & Surat Jalan')
@section('active_page', 'delivery')

@push('styles')
<style>
    .delivery-zebra tbody tr:nth-child(even) { background: rgba(242, 243, 255, .55); }
    .delivery-overlay { backdrop-filter: blur(10px); background: rgba(15, 23, 42, .48); }
    .qty-invalid { border-color: #ba1a1a !important; box-shadow: 0 0 0 2px rgba(186, 26, 26, .12); }
</style>
@endpush

@section('content')
<div class="space-y-lg">
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-md">
        <div>
            <nav class="flex items-center gap-xs text-label-md text-on-surface-variant mb-xs uppercase tracking-wider">
                <span>Transaksi</span>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="text-primary font-bold">Surat Jalan</span>
            </nav>
            <h2 class="font-headline-xl text-headline-xl text-on-surface">Surat Jalan &amp; Logistik</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-xs">Kelola split-shipment tanpa melampaui saldo barang pada invoice.</p>
        </div>
        <button class="bg-primary text-on-primary px-lg py-3 rounded-xl font-bold flex items-center gap-sm shadow-lg shadow-primary/20" type="button" onclick="openCreateDeliveryModal()">
            <span class="material-symbols-outlined">add</span>Buat Surat Jalan
        </button>
    </div>

    <form method="GET" action="{{ route('delivery.index') }}" class="flex flex-col xl:flex-row xl:items-center justify-between gap-md bg-surface-container-lowest p-md rounded-xl border border-outline-variant">
        <div class="flex flex-col md:flex-row gap-sm">
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="rounded-xl border-outline-variant">
            <input type="date" name="date_to" value="{{ $dateTo }}" class="rounded-xl border-outline-variant">
        </div>
        <div class="flex flex-col md:flex-row gap-sm w-full xl:w-auto">
            <div class="relative flex-1 xl:w-80">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">search</span>
                <input class="w-full pl-10 pr-4 py-2 bg-surface border border-outline-variant rounded-xl" name="search" value="{{ $search }}" placeholder="Cari surat jalan, invoice, customer...">
            </div>
            <button class="px-lg py-2 bg-secondary text-on-secondary rounded-xl font-bold" type="submit">Terapkan</button>
            <a class="px-lg py-2 bg-surface-container-high text-on-surface rounded-xl font-bold text-center" href="{{ route('delivery.index') }}">Reset</a>
        </div>
    </form>

    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left delivery-zebra min-w-[980px]">
                <thead class="bg-surface-container-low text-label-md text-on-surface-variant uppercase">
                    <tr>
                        <th class="px-lg py-md">No. Surat Jalan</th>
                        <!-- <th class="px-lg py-md">Invoice</th> -->
                        <th class="px-lg py-md">Customer</th>
                        <th class="px-lg py-md">Tujuan</th>
                        <th class="px-lg py-md">Armada</th>
                        <th class="px-lg py-md">Status</th>
                        <th class="px-lg py-md text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deliveries as $delivery)
                        @php
                            $badge = match($delivery->status) {
                                'DITERIMA' => 'bg-primary-container/20 text-primary',
                                'DI JALAN' => 'bg-secondary/10 text-secondary',
                                default => 'bg-amber-100 text-amber-700',
                            };
                        @endphp
                        <tr>
                            <td class="px-lg py-md">
                                <button class="font-bold text-secondary hover:underline" type="button" onclick="openDeliveryDetail({{ $delivery->id }})">{{ $delivery->no_surat_jalan }}</button>
                                <p class="text-[11px] text-on-surface-variant">{{ optional($delivery->tgl_terbit)->format('d M Y H:i') }}</p>
                            </td>
                            <!-- <td class="px-lg py-md font-semibold">{{ $delivery->no_invoice ?? '-' }}</td> -->
                            <td class="px-lg py-md">{{ $delivery->nama_pelanggan ?? '-' }}</td>
                            <td class="px-lg py-md max-w-xs truncate">{{ $delivery->alamat_tujuan ?: 'Alamat belum tersedia' }}</td>
                            <td class="px-lg py-md">
                                <p class="font-semibold">{{ $delivery->nama_sopir }}</p>
                                <p class="text-label-sm text-on-surface-variant">{{ $delivery->plat_nomor }}</p>
                            </td>
                            <td class="px-lg py-md"><span class="px-3 py-1 rounded-full text-label-sm font-bold {{ $badge }}">{{ $delivery->status }}</span></td>
                            <td class="px-lg py-md">
                                <div class="flex justify-center gap-xs">
                                    <button class="p-2 rounded-full hover:bg-secondary/10" type="button" onclick="openDeliveryDetail({{ $delivery->id }})"><span class="material-symbols-outlined">visibility</span></button>
                                    <a class="p-2 rounded-full hover:bg-primary/10" href="{{ route('delivery.print', $delivery->id) }}" target="_blank"><span class="material-symbols-outlined">print</span></a>
                                    @if ($delivery->status !== 'DITERIMA')
                                    <button class="p-2 rounded-full hover:bg-tertiary-container/20" 
                                            type="button" 
                                            data-id="{{ $delivery->id }}"
                                            data-number="{{ $delivery->no_surat_jalan }}"
                                            data-status="{{ $delivery->status }}"
                                            data-receiver="{{ $delivery->penerima_lokasi }}"
                                            onclick="openStatusModal(this.dataset)">
                                        <span class="material-symbols-outlined">settings</span>
                                    </button>
                                @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-lg py-xl text-center text-on-surface-variant">Belum ada surat jalan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-lg py-md border-t border-outline-variant">{{ $deliveries->links() }}</div>
    </div>
</div>
@endsection

@push('modals')
<div class="hidden fixed inset-0 z-[100] items-center justify-center p-md delivery-overlay" id="create-delivery-modal">
    <div class="bg-white w-full max-w-5xl rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh]">
        <div class="px-lg py-md border-b border-outline-variant flex justify-between bg-surface-container-low">
            <div><h2 class="font-headline-md">Form Penerbitan Surat Jalan</h2><p class="text-label-sm text-on-surface-variant">Saldo kirim dihitung otomatis.</p></div>
            <button type="button" onclick="closeModal('create-delivery-modal')"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form id="deliveryForm" method="POST" action="{{ route('delivery.store') }}">
            @csrf
            <div class="p-lg overflow-y-auto space-y-lg max-h-[72vh]">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
                    <div class="space-y-sm">
                        <label class="font-label-md uppercase">Invoice Penjualan</label>
                        <select class="w-full rounded-xl border-outline-variant" id="delivery_invoice_id" name="penjualan_id" required>
                            <option value="">Pilih invoice yang masih memiliki saldo kirim...</option>
                            @foreach ($invoices as $invoice)
                                <option value="{{ $invoice->id }}">{{ $invoice->no_invoice }} · {{ $invoice->nama_pelanggan }} · Sisa {{ (int)$invoice->total_sisa }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-sm">
                        <label class="font-label-md uppercase">Customer</label>
                        <input class="w-full rounded-xl bg-surface-container-low border-outline-variant" id="delivery_customer_name" readonly value="-">
                    </div>
                </div>
                <div class="space-y-sm">
                    <label class="font-label-md uppercase">Alamat Tujuan</label>
                    <textarea class="w-full rounded-xl bg-surface-container-low border-outline-variant" id="delivery_address" readonly>-</textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
                    <div class="space-y-sm"><label class="font-label-md uppercase">Nama Sopir</label><input class="w-full rounded-xl border-outline-variant" id="delivery_driver" name="nama_sopir" required></div>
                    <div class="space-y-sm"><label class="font-label-md uppercase">Nomor Plat</label><input class="w-full rounded-xl border-outline-variant uppercase" id="delivery_plate" name="plat_nomor" required></div>
                </div>
                <div class="space-y-sm"><label class="font-label-md uppercase">Catatan</label><textarea class="w-full rounded-xl border-outline-variant" name="catatan"></textarea></div>
                <div>
                    <div class="flex justify-between mb-md">
                        <div><h3 class="font-headline-md">Material Split-Shipping</h3><p class="text-label-sm text-on-surface-variant">Qty hari ini tidak boleh melebihi sisa.</p></div>
                        <div id="invoice-loading" class="hidden items-center gap-xs text-primary"><span class="material-symbols-outlined animate-spin">progress_activity</span>Memuat...</div>
                    </div>
                    <div class="border border-outline-variant rounded-xl overflow-x-auto">
                        <table class="w-full min-w-[820px]">
                            <thead class="bg-surface-container-low text-label-md uppercase">
                                <tr><th class="px-md py-sm text-left">Barang</th><th>Total Order</th><th>Terkirim</th><th>Sisa</th><th class="w-56">Qty Hari Ini</th></tr>
                            </thead>
                            <tbody id="delivery-items-body"><tr><td colspan="5" class="px-md py-xl text-center text-on-surface-variant">Pilih invoice.</td></tr></tbody>
                        </table>
                    </div>
                    <p class="hidden text-error font-bold mt-sm" id="delivery-global-error">Muatan melebihi sisa pesanan.</p>
                </div>
            </div>
            <div class="px-lg py-md border-t border-outline-variant flex justify-end gap-md bg-surface-container-low">
                <button class="px-lg py-2 border rounded-xl" type="button" onclick="closeModal('create-delivery-modal')">Batal</button>
                <button class="bg-primary text-on-primary px-xl py-2 rounded-xl disabled:opacity-40" id="preview-delivery-button" type="button" disabled onclick="showDeliverySummary()">Pratinjau Surat Jalan</button>
            </div>
        </form>
    </div>
</div>

<div class="hidden fixed inset-0 z-[110] items-center justify-center p-md delivery-overlay" id="delivery-summary-modal">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-lg bg-primary text-on-primary"><h3 class="font-headline-md">Ringkasan Manifest</h3></div>
        <div class="p-lg space-y-md">
            <div class="grid grid-cols-2 gap-md"><div class="p-md bg-surface-container-low rounded-xl"><p class="text-label-sm">Sopir</p><p class="font-bold" id="summary-driver">-</p></div><div class="p-md bg-surface-container-low rounded-xl"><p class="text-label-sm">Plat</p><p class="font-bold" id="summary-plate">-</p></div></div>
            <div class="p-md border rounded-xl"><p class="font-bold" id="summary-customer">-</p><p class="text-body-md text-on-surface-variant" id="summary-address">-</p></div>
            <div class="flex justify-between p-md bg-primary/5 rounded-xl"><span>Total Jenis Barang</span><strong id="summary-item-count">0</strong></div>
            <button class="w-full py-3 bg-surface-container-high rounded-xl" type="button" onclick="closeModal('delivery-summary-modal')">Periksa Lagi</button>
            <button class="w-full py-3 bg-primary text-on-primary rounded-xl" id="execute-delivery-button" type="button">Ya, Simpan & Cetak 3 Rangkap</button>
        </div>
    </div>
</div>

<div class="hidden fixed inset-0 z-[120] items-center justify-center p-md delivery-overlay" id="update-status-modal">
    <div class="bg-white w-full max-w-md rounded-2xl p-lg space-y-lg">
        <div><h3 class="font-headline-md">Update Status</h3><p class="text-label-sm" id="status-delivery-number">-</p></div>
        <form id="statusForm">
            @csrf
            <input type="hidden" id="status-delivery-id">
            <div class="space-y-sm">
                @foreach (['DIPROSES'=>'Material sedang dimuat','DI JALAN'=>'Armada menuju lokasi','DITERIMA'=>'Material telah diterima'] as $value => $text)
                    <label class="flex gap-md p-md border rounded-xl"><input class="w-5 h-5 text-primary" name="status" type="radio" value="{{ $value }}"><div><p class="font-bold">{{ $value }}</p><p class="text-label-sm">{{ $text }}</p></div></label>
                @endforeach
                <div class="hidden" id="receiver-input-container"><label class="font-label-md uppercase">Nama Penerima</label><input class="w-full rounded-xl border-primary" id="receiver-name-input" name="penerima_lokasi"></div>
            </div>
            <div class="flex gap-md pt-lg"><button class="flex-1 py-2 border rounded-xl" type="button" onclick="closeModal('update-status-modal')">Batal</button><button class="flex-1 py-2 bg-primary text-on-primary rounded-xl" id="save-status-button" type="submit">Simpan</button></div>
        </form>
    </div>
</div>

<div class="hidden fixed inset-0 z-[120] items-center justify-center p-md delivery-overlay" id="delivery-detail-modal">
    <div class="bg-white w-full max-w-3xl rounded-2xl overflow-hidden">
        <div class="px-lg py-md bg-surface-container-low flex justify-between"><div><h3 class="font-headline-md">Detail Surat Jalan</h3><p id="detail-number">-</p></div><button type="button" onclick="closeModal('delivery-detail-modal')"><span class="material-symbols-outlined">close</span></button></div>
        <div class="p-lg space-y-md max-h-[75vh] overflow-y-auto">
            <div class="grid grid-cols-2 gap-md"><div class="p-md bg-surface-container-low rounded-xl" id="detail-customer">-</div><div class="p-md bg-surface-container-low rounded-xl" id="detail-fleet">-</div></div>
            <p id="detail-address">-</p><p id="detail-notes">-</p>
            <table class="w-full border"><thead class="bg-surface-container-low"><tr><th class="p-md text-left">Barang</th><th class="p-md text-right">Qty</th></tr></thead><tbody id="detail-items-body"></tbody></table>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
const invoiceUrl = @json(route('delivery.invoice_detail', ['id' => '__ID__']));
const showUrl = @json(route('delivery.show', ['id' => '__ID__']));
const storeUrl = @json(route('delivery.store'));
const statusUrl = @json(route('delivery.update_status', ['id' => '__ID__']));
const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
let submitting = false;

function esc(v){return String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));}
function openModal(id){const m=document.getElementById(id);m.classList.remove('hidden');m.classList.add('flex');document.body.classList.add('modal-open');}
function closeModal(id){const m=document.getElementById(id);m.classList.add('hidden');m.classList.remove('flex');if(!document.querySelector('.delivery-overlay.flex'))document.body.classList.remove('modal-open');}
function openCreateDeliveryModal(){document.getElementById('deliveryForm').reset();document.getElementById('delivery_customer_name').value='-';document.getElementById('delivery_address').value='-';document.getElementById('delivery-items-body').innerHTML='<tr><td colspan="5" class="px-md py-xl text-center">Pilih invoice.</td></tr>';document.getElementById('preview-delivery-button').disabled=true;openModal('create-delivery-modal');}

async function loadInvoice(id){
    if(!id)return;
    const loading=document.getElementById('invoice-loading');loading.classList.remove('hidden');loading.classList.add('flex');
    try{
        const r=await fetch(invoiceUrl.replace('__ID__',encodeURIComponent(id)),{headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});
        const p=await r.json();if(!r.ok||!p.success)throw new Error(p.message||'Gagal memuat invoice.');
        document.getElementById('delivery_customer_name').value=p.data.invoice.nama_pelanggan||'-';
        document.getElementById('delivery_address').value=p.data.invoice.alamat_tujuan||'-';
        renderItems(p.data.items||[]);
    }catch(e){document.getElementById('delivery-items-body').innerHTML=`<tr><td colspan="5" class="p-lg text-center text-error">${esc(e.message)}</td></tr>`;}
    finally{loading.classList.add('hidden');loading.classList.remove('flex');}
}

function renderItems(items){
    const body=document.getElementById('delivery-items-body');
    body.innerHTML=items.map(i=>`<tr class="border-t">
        <td class="p-md text-left"><input type="hidden" name="kode_barang[]" value="${esc(i.kode_barang)}"><p class="font-bold">${esc(i.nama_barang)}</p><p class="text-label-sm">${esc(i.kode_barang)} · ${esc(i.satuan)}</p></td>
        <td class="p-md text-center">${Number(i.qty_order).toLocaleString('id-ID', { maximumFractionDigits: 3 })}</td>
        <td class="p-md text-center">${Number(i.qty_terkirim).toLocaleString('id-ID', { maximumFractionDigits: 3 })}</td>
        <td class="p-md text-center font-bold">${Number(i.sisa_kirim).toLocaleString('id-ID', { maximumFractionDigits: 3 })}</td>
        <td class="p-md"><input class="delivery-qty-input w-full rounded-lg text-center ${Number(i.sisa_kirim)<=0?'bg-surface-container':''}" data-max="${Number(i.sisa_kirim)}" max="${Number(i.sisa_kirim)}" min="0" step="any" name="qty_kirim[]" value="0" type="number" ${Number(i.sisa_kirim)<=0?'readonly':''}><p class="delivery-qty-error hidden text-error text-label-sm">Muatan melebihi sisa pesanan!</p></td>
    </tr>`).join('');
    document.querySelectorAll('.delivery-qty-input').forEach(i=>i.addEventListener('input',validateQty));validateQty();
}

function validateQty(){
    let invalid=false,positive=false;
    document.querySelectorAll('.delivery-qty-input').forEach(i=>{const v=Number(i.value||0),m=Number(i.dataset.max||0),bad=v<0||v>m;i.classList.toggle('qty-invalid',bad);i.parentElement.querySelector('.delivery-qty-error')?.classList.toggle('hidden',!bad);if(v>0&&!bad)positive=true;if(bad)invalid=true;});
    const ready=!invalid&&positive&&document.getElementById('delivery_invoice_id').value&&document.getElementById('delivery_driver').value.trim()&&document.getElementById('delivery_plate').value.trim();
    document.getElementById('preview-delivery-button').disabled=!ready;document.getElementById('delivery-global-error').classList.toggle('hidden',!invalid);return ready;
}

function showDeliverySummary(){
    if(!validateQty())return alert('Lengkapi data dan periksa qty.');
    document.getElementById('summary-driver').innerText=document.getElementById('delivery_driver').value;
    document.getElementById('summary-plate').innerText=document.getElementById('delivery_plate').value.toUpperCase();
    document.getElementById('summary-customer').innerText=document.getElementById('delivery_customer_name').value;
    document.getElementById('summary-address').innerText=document.getElementById('delivery_address').value;
    document.getElementById('summary-item-count').innerText=[...document.querySelectorAll('.delivery-qty-input')].filter(i=>Number(i.value)>0).length;
    openModal('delivery-summary-modal');
}

async function saveDelivery(){
    if(submitting||!validateQty())return;
    submitting=true;const b=document.getElementById('execute-delivery-button');b.disabled=true;b.innerText='Menyimpan & mencetak...';
    try{
        const r=await fetch(storeUrl,{method:'POST',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':csrf},body:new FormData(document.getElementById('deliveryForm'))});
        const p=await r.json();if(!r.ok||!p.success)throw new Error(p.errors?Object.values(p.errors).flat().join('\n'):(p.message||'Gagal menyimpan.'));
        if(!p.native_printed&&p.print_url)window.open(p.print_url,'_blank','noopener');
        alert(`${p.message}\n${p.no_surat_jalan}\n${p.print_message||''}`);location.reload();
    }catch(e){alert(e.message);}finally{submitting=false;b.disabled=false;b.innerText='Ya, Simpan & Cetak 3 Rangkap';}
}

function openStatusModal(d){document.getElementById('status-delivery-id').value=d.id;document.getElementById('status-delivery-number').innerText=d.number;document.getElementById('receiver-name-input').value=d.receiver||'';document.querySelectorAll('#statusForm [name=status]').forEach(i=>i.checked=i.value===d.status);toggleReceiver(d.status==='DITERIMA');openModal('update-status-modal');}
function toggleReceiver(show){document.getElementById('receiver-input-container').classList.toggle('hidden',!show);const i=document.getElementById('receiver-name-input');show?i.setAttribute('required','required'):i.removeAttribute('required');}

async function saveStatus(e){
    e.preventDefault();const id=document.getElementById('status-delivery-id').value;
    try{const r=await fetch(statusUrl.replace('__ID__',id),{method:'POST',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':csrf},body:new FormData(e.currentTarget)});const p=await r.json();if(!r.ok||!p.success)throw new Error(p.errors?Object.values(p.errors).flat().join('\n'):p.message);alert(p.message);location.reload();}catch(err){alert(err.message);}
}

async function openDeliveryDetail(id){
    openModal('delivery-detail-modal');
    try{const r=await fetch(showUrl.replace('__ID__',id),{headers:{Accept:'application/json'}});const p=await r.json();if(!r.ok||!p.success)throw new Error(p.message);const d=p.data;document.getElementById('detail-number').innerText=`${d.no_surat_jalan} · ${d.status}`;document.getElementById('detail-customer').innerText=`${d.no_invoice} · ${d.nama_pelanggan}`;document.getElementById('detail-fleet').innerText=`${d.nama_sopir} · ${d.plat_nomor}`;document.getElementById('detail-address').innerText=d.alamat_tujuan;document.getElementById('detail-notes').innerText=d.catatan;document.getElementById('detail-items-body').innerHTML=d.items.map(i=>`<tr><td class="p-md"><b>${esc(i.nama_barang)}</b><br><small>${esc(i.kode_barang)}</small></td><td class="p-md text-right font-bold">${Number(i.qty_kirim).toLocaleString('id-ID')} ${esc(i.satuan)}</td></tr>`).join('');}catch(e){alert(e.message);closeModal('delivery-detail-modal');}
}

document.addEventListener('DOMContentLoaded',()=>{
    document.getElementById('delivery_invoice_id').addEventListener('change',e=>loadInvoice(e.target.value));
    document.getElementById('delivery_driver').addEventListener('input',validateQty);
    document.getElementById('delivery_plate').addEventListener('input',validateQty);
    document.getElementById('execute-delivery-button').addEventListener('click',saveDelivery);
    document.getElementById('statusForm').addEventListener('submit',saveStatus);
    document.querySelectorAll('#statusForm [name=status]').forEach(i=>i.addEventListener('change',()=>toggleReceiver(i.value==='DITERIMA')));
});
</script>
@endpush
