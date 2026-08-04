import './bootstrap';
// resources/js/app.js

// --- MANAJEMEN MODAL UTAMA ---
window.openModal = function(id) {
    const modal = document.getElementById(id);
    if(!modal) return;
    const content = modal.querySelector('.glass-modal');
    modal.classList.remove('opacity-0', 'pointer-events-none');
    if(content) {
        setTimeout(function(){ content.classList.remove('translate-y-4'); }, 10);
    }
} // [cite: 4]

window.closeModal = function(id) {
    const modal = document.getElementById(id);
    if(!modal) return;
    const content = modal.querySelector('.glass-modal');
    if(content) content.classList.add('translate-y-4');
    setTimeout(function(){
        modal.classList.add('opacity-0', 'pointer-events-none');
        if(typeof window.resetComboboxForm === 'function') window.resetComboboxForm();
    }, 200);
} // [cite: 4, 5]

// --- COMBOBOX & LOGIC LIVE PREVIEW ---
// Ambil element setelah DOM Loaded
document.addEventListener('DOMContentLoaded', function() {
    const comboboxContainer = document.getElementById('comboboxContainer');
    const comboboxInput = document.getElementById('comboboxInput');
    const comboboxDropdown = document.getElementById('comboboxDropdown');
    const comboboxArrow = document.getElementById('comboboxArrow');
    const comboboxItems = document.querySelectorAll('.combobox-item');
    const comboboxEmpty = document.getElementById('comboboxEmpty');

    const hiddenProductInput = document.getElementById('productSelect');
    const satuanInput = document.getElementById('product_satuan');
    const stokSistemInput = document.getElementById('product_stok_sistem');
    const stokFisikInput = document.getElementById('stok_fisik');
    const previewBox = document.getElementById('preview_selisih_box');
    const previewText = document.getElementById('preview_selisih_text');
    const previewLabel = document.getElementById('preview_selisih_label');
    const adjustmentForm = document.getElementById('adjustmentForm');
    const executeAdjustmentSubmitBtn = document.getElementById('executeAdjustmentSubmitBtn');
    let allowAdjustmentSubmit = false; // [cite: 5, 6]

    function resetPreviewClass() {
        if(previewBox) previewBox.className = 'w-full bg-surface-container-low border border-outline-variant rounded-lg px-md py-2.5 flex items-center gap-2 transition-all';
        if(previewText) previewText.className = 'font-body-md font-bold text-on-surface-variant';
        if(previewLabel) previewLabel.className = 'text-xs text-on-surface-variant font-medium uppercase';
    } // [cite: 6]

    function parseLocaleFloat(val) {
        if (val === null || val === undefined || val === '') return 0;
        const clean = String(val).trim().replace(',', '.');
        const num = parseFloat(clean);
        return isNaN(num) ? 0 : num;
    }

    function formatQtyJs(val) {
        const num = parseLocaleFloat(val);
        const abs = Math.abs(num);
        const sign = num < 0 ? '-' : '';
        if (Math.floor(abs) === abs) {
            return sign + Math.floor(abs).toString();
        }
        let str = abs.toFixed(3).replace(/\.?0+$/, '');
        return sign + str.replace('.', ',');
    }

    window.calculateSelisihPreview = function() {
        if (!stokSistemInput || !stokFisikInput || !previewText) return;

        const stokSistemRaw = stokSistemInput.getAttribute('data-raw-stok');
        const stokSistem = (stokSistemRaw !== null && stokSistemRaw !== '') 
            ? parseLocaleFloat(stokSistemRaw) 
            : parseLocaleFloat(stokSistemInput.value || '0');

        const stokFisik = parseLocaleFloat(stokFisikInput.value || '0');
        const selisih = stokFisik - stokSistem;
        const prefix = selisih > 0 ? '+' : '';

        previewText.textContent = prefix + formatQtyJs(selisih);
        resetPreviewClass();

        if (selisih < 0) {
            if(previewBox) previewBox.className = 'w-full bg-error/10 border border-error/20 rounded-lg px-md py-2.5 flex items-center gap-2 transition-all';
            previewText.className = 'font-body-md font-bold text-error';
            if(previewLabel) {
                previewLabel.textContent = 'Shrinkage (Penyusutan/Hilang)';
                previewLabel.className = 'text-xs text-error font-medium uppercase';
            }
        } else if (selisih > 0) {
            if(previewBox) previewBox.className = 'w-full bg-primary/10 border border-primary/20 rounded-lg px-md py-2.5 flex items-center gap-2 transition-all';
            previewText.className = 'font-body-md font-bold text-primary';
            if(previewLabel) {
                previewLabel.textContent = 'Surplus (Berlebih)';
                previewLabel.className = 'text-xs text-primary font-medium uppercase';
            }
        } else {
            if(previewLabel) previewLabel.textContent = 'Balanced';
        }
    }

    if (comboboxInput && comboboxDropdown) {
        comboboxInput.addEventListener('focus', function() {
            comboboxDropdown.classList.remove('hidden');
            if(comboboxArrow) comboboxArrow.classList.add('rotate-180');
            filterCombobox(this.value);
        });

        comboboxInput.addEventListener('input', function() {
            filterCombobox(this.value);
        });

        comboboxItems.forEach(item => {
            item.addEventListener('click', function() {
                const code = this.getAttribute('data-value');
                const name = this.getAttribute('data-nama');
                const satuan = this.getAttribute('data-satuan') || '-';
                const stok = parseLocaleFloat(this.getAttribute('data-stok') || '0');

                comboboxInput.value = `${code} - ${name}`;
                if(hiddenProductInput) hiddenProductInput.value = code;
                if(satuanInput) satuanInput.value = satuan;
                if(stokSistemInput) {
                    stokSistemInput.value = formatQtyJs(stok);
                    stokSistemInput.setAttribute('data-raw-stok', stok);
                }

                window.calculateSelisihPreview();
                comboboxDropdown.classList.add('hidden');
                if(comboboxArrow) comboboxArrow.classList.remove('rotate-180');
            });
        });

        document.addEventListener('click', function(e) {
            if (comboboxContainer && !comboboxContainer.contains(e.target)) {
                comboboxDropdown.classList.add('hidden');
                if(comboboxArrow) comboboxArrow.classList.remove('rotate-180');
            }
        });
    } // [cite: 6, 7, 8]

    function filterCombobox(query) {
        const cleanQuery = query.toLowerCase().trim();
        let matchCount = 0;
        comboboxItems.forEach(item => {
            const code = item.getAttribute('data-value').toLowerCase();
            const name = item.getAttribute('data-nama').toLowerCase();
            if (code.includes(cleanQuery) || name.includes(cleanQuery)) {
                item.classList.remove('hidden');
                matchCount++;
            } else {
                item.classList.add('hidden');
            }
        });
        if(comboboxEmpty) {
            if (matchCount === 0) comboboxEmpty.classList.remove('hidden');
            else comboboxEmpty.classList.add('hidden');
        }
    } // [cite: 8, 9]

    window.resetComboboxForm = function() {
        if (adjustmentForm) adjustmentForm.reset();
        if (hiddenProductInput) hiddenProductInput.value = '';
        if (comboboxInput) comboboxInput.value = '';
        if (satuanInput) satuanInput.value = '-';
        if (stokSistemInput) stokSistemInput.value = '0';
        window.calculateSelisihPreview();
    } // [cite: 9, 10]

    if (stokFisikInput) {
        stokFisikInput.addEventListener('input', window.calculateSelisihPreview);
    } // [cite: 15]

    if (adjustmentForm) {
        adjustmentForm.addEventListener('submit', function(event) {
            if (allowAdjustmentSubmit) return true;
            event.preventDefault();

            if (!hiddenProductInput.value || !comboboxInput.value) {
                alert('Produk wajib dicari dan dipilih terlebih dahulu.');
                return false;
            }
            if (!stokFisikInput.value) {
                alert('Stok fisik wajib diisi.');
                return false;
            }
            const keterangan = document.getElementById('keterangan').value.trim();
            if (keterangan.length < 5) {
                alert('Keterangan minimal 5 karakter.');
                return false;
            }
            if(typeof window.showConfirmAdjustmentModal === 'function') window.showConfirmAdjustmentModal();
            return false;
        });
    } // [cite: 15]

    if (executeAdjustmentSubmitBtn) {
        executeAdjustmentSubmitBtn.addEventListener('click', function() {
            allowAdjustmentSubmit = true;
            if(typeof window.hideConfirmAdjustmentModal === 'function') window.hideConfirmAdjustmentModal();
            if(adjustmentForm) adjustmentForm.submit();
        });
    } // [cite: 16]
});

// --- MANAJEMEN MODAL EDIT & DETAIL ---
window.showConfirmAdjustmentModal = function() {
    const hiddenProductInput = document.getElementById('productSelect');
    const previewText = document.getElementById('preview_selisih_text');
    const comboboxItems = document.querySelectorAll('.combobox-item');
    const confirmAdjustmentModal = document.getElementById('confirmAdjustmentModal');

    if(!hiddenProductInput || !confirmAdjustmentModal) return;
    const selectedCode = hiddenProductInput.value;
    let productName = '-';

    comboboxItems.forEach(item => {
        if (item.getAttribute('data-value') === selectedCode) {
            productName = item.getAttribute('data-nama');
        }
    });

    document.getElementById('confirmProductName').textContent = productName;
    document.getElementById('confirmSelisihValue').textContent = previewText ? previewText.textContent : '';
    document.getElementById('confirmSelisihValue').className = previewText ? previewText.className : '';
    document.getElementById('confirmAdjustmentText').textContent = 'Apakah Anda yakin ingin menyimpan draft adjustment ini ke database MySQL?';

    confirmAdjustmentModal.classList.remove('hidden');
    confirmAdjustmentModal.classList.add('flex');
} // [cite: 11, 12]

window.hideConfirmAdjustmentModal = function() {
    const modal = document.getElementById('confirmAdjustmentModal');
    if(modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

window.showReadOnlyDetail = function(payload) {
    const modal = document.getElementById('detailAdjustmentModal');
    const container = document.getElementById('detailAdjustmentContent');
    if (!modal || !container) return;

    function safeFloat(val) {
        if (val === null || val === undefined || val === '') return 0;
        const clean = String(val).trim().replace(',', '.');
        const num = parseFloat(clean);
        return isNaN(num) ? 0 : num;
    }

    function safeFormat(val) {
        const num = safeFloat(val);
        const abs = Math.abs(num);
        const sign = num < 0 ? '-' : '';
        if (Math.floor(abs) === abs) {
            return sign + Math.floor(abs).toString();
        }
        let str = abs.toFixed(3).replace(/\.?0+$/, '');
        return sign + str.replace('.', ',');
    }

    const stokSistemFmt = safeFormat(payload.stok_sistem);
    const stokFisikFmt = safeFormat(payload.stok_fisik);
    const selisihNum = safeFloat(payload.selisih);
    const selisihPrefix = selisihNum > 0 ? '+' : '';
    const selisihFmt = selisihPrefix + safeFormat(selisihNum);

    const rows = [
        ['Tanggal Adjustment', payload.tanggal || '-'],
        ['Nama Barang', payload.barang || '-'],
        ['Satuan (UoM)', payload.satuan || '-'],
        ['Stok Sistem', stokSistemFmt],
        ['Stok Fisik Gudang', stokFisikFmt],
        ['Selisih Audit', selisihFmt],
        ['Staf Gudang (Input)', payload.staf_gudang || '-'],
        ['Verifikasi (Supervisor)', payload.diverifikasi_oleh || '-'],
        ['Status Sync', payload.status || '-'],
        ['Alasan / Keterangan', payload.keterangan || '-']
    ];
    
    container.innerHTML = '';
    const wrapper = document.createElement('div');
    wrapper.className = 'w-full space-y-2.5';

    rows.forEach(function (row) {
        const card = document.createElement('div');
        card.className = 'flex flex-col sm:flex-row sm:items-center sm:justify-between rounded-xl border border-outline-variant/40 bg-surface-container-low p-3 gap-1';
        
        const labelEl = document.createElement('span');
        labelEl.className = 'text-xs font-bold uppercase tracking-wider text-on-surface-variant';
        labelEl.textContent = row[0];
        
        const valueEl = document.createElement('span');
        valueEl.className = 'font-bold text-on-surface text-sm break-words';
        if (row[0] === 'Selisih Audit') {
            valueEl.className += selisihNum < 0 ? ' text-error font-black' : (selisihNum > 0 ? ' text-primary font-black' : ' text-on-surface');
        }
        valueEl.textContent = row[1];
        
        card.appendChild(labelEl);
        card.appendChild(valueEl);
        wrapper.appendChild(card);
    });

    container.appendChild(wrapper);
    
    modal.classList.remove('hidden', 'opacity-0', 'pointer-events-none');
    modal.classList.add('flex');
    modal.removeAttribute('aria-hidden');
    modal.removeAttribute('inert');
    modal.style.removeProperty('pointer-events');

    if (window.TB39Interaction && typeof window.TB39Interaction.refresh === 'function') {
        window.TB39Interaction.refresh();
    }
    if (window.TB39UiStability && typeof window.TB39UiStability.refresh === 'function') {
        window.TB39UiStability.refresh();
    }
}

window.hideDetailAdjustmentModal = function() {
    const modal = document.getElementById('detailAdjustmentModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.setAttribute('aria-hidden', 'true');

    if (window.TB39Interaction && typeof window.TB39Interaction.refresh === 'function') {
        window.TB39Interaction.refresh();
    }
    if (window.TB39UiStability && typeof window.TB39UiStability.refresh === 'function') {
        window.TB39UiStability.refresh();
    }
}

// --- SWITCH KATEGORI PELANGGAN ---
window.switchKategori = function(type, element) {
    const inputKat = document.getElementById('kategori_input');
    if(inputKat) inputKat.value = type;

    const buttons = document.querySelectorAll('#kategori-togglebutton');
    buttons.forEach(btn => {
        btn.classList.remove('bg-primary', 'text-on-primary');
        btn.classList.add('hover:bg-surface-container-highest', 'text-on-surface-variant');
    });

    element.classList.remove('hover:bg-surface-container-highest', 'text-on-surface-variant');
    element.classList.add('bg-primary', 'text-on-primary');
} // [cite: 17]

// --- FILTER INVENTARIS ---
window.applyInventoryFilters = function() {
    const searchInput = document.getElementById('tableSearch');
    const toggle = document.getElementById('outOfStockToggle');
    const btnExportPdf = document.getElementById('btnExportPdf');

    const term = (searchInput ? searchInput.value : '').toLowerCase().trim();
    const onlyOutOfStock = toggle ? toggle.checked : false;

    document.querySelectorAll('.inventory-row').forEach(function(row) {
        const rowSearch = row.getAttribute('data-search') || '';
        const stock = Number(row.getAttribute('data-stock') || 0);
        const matchesText = rowSearch.includes(term);
        const matchesStock = onlyOutOfStock ? stock === 0 : true;

        if (matchesText && matchesStock) {
            row.classList.remove('hidden-row');
        } else {
            row.classList.add('hidden-row');
        }
    });

    if (btnExportPdf && typeof baseExportUrl !== 'undefined') {
        const url = new URL(baseExportUrl, window.location.origin);
        if (term) url.searchParams.set('search', term);
        if (onlyOutOfStock) url.searchParams.set('out_of_stock', '1');
        btnExportPdf.href = url.pathname + url.search;
    }
} // [cite: 26, 27, 28]

// --- INITIALIZE LIVE OTHERS ---
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('tableSearch');
    const toggle = document.getElementById('outOfStockToggle');
    const clock = document.getElementById('clock-display');

    if (searchInput) searchInput.addEventListener('input', window.applyInventoryFilters);
    if (toggle) toggle.addEventListener('change', window.applyInventoryFilters);
    if (clock) {
        setInterval(function() {
            const now = new Date();
            clock.innerText = now.toLocaleTimeString('id-ID', { hour12: false });
        }, 30000);
    }
}); // [cite: 31]


/* CUSTOMER_MODAL_SWITCH_FIX_START */
(function () {
    function normalizeCustomerKategori(value) {
        const category = String(value || '').trim().toLowerCase();

        if (category === 'toko') {
            return 'Toko';
        }

        if (category === 'sales') {
            return 'Sales';
        }

        return 'Normal';
    }

    window.normalizeKategori = window.normalizeKategori || normalizeCustomerKategori;

    window.switchKategori = function (category, element) {
        const normalized = normalizeCustomerKategori(category);
        const input = document.getElementById('kategori_input');
        let buttons = Array.from(document.querySelectorAll('#kategori-toggle .kategori-button'));

        if (input) {
            input.value = normalized;
        }

        if (!buttons.length && element) {
            buttons = [element];
        }

        buttons.forEach(function (button) {
            const buttonCategory = normalizeCustomerKategori(
                button.dataset ? button.dataset.category : category
            );
            const isActive = buttonCategory === normalized;

            button.classList.remove(
                'bg-primary',
                'text-on-primary',
                'bg-transparent',
                'text-on-surface-variant',
                'hover:bg-surface-container-highest',
                'shadow-sm'
            );

            if (isActive) {
                button.classList.add('bg-primary', 'text-on-primary', 'shadow-sm');
            } else {
                button.classList.add(
                    'bg-transparent',
                    'text-on-surface-variant',
                    'hover:bg-surface-container-highest'
                );
            }

            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('kategori_input');

        if (input && document.getElementById('kategori-toggle')) {
            window.switchKategori(input.value || 'Normal');
        }
    });
})();
/* CUSTOMER_MODAL_SWITCH_FIX_END */

/* TB39_INTERACTION_STABILIZER_START
 * NativePHP desktop interaction stabilizer.
 * Tujuan: cegah modal/overlay tersembunyi menahan klik, tanpa MutationObserver berat.
 */
(function () {
    'use strict';

    if (window.__TB39_INTERACTION_STABILIZER__ === true) {
        return;
    }
    window.__TB39_INTERACTION_STABILIZER__ = true;

    const BLOCKER_SELECTOR = [
        '.tb-modal-root',
        '.modal-overlay',
        '[role="dialog"]',
        '#purchaseModal',
        '#purchaseDetailModal',
        '#confirmPurchaseModal',
        '#pos-modal',
        '#productSelectionModal',
        '#confirmSubmitModal',
        '#customer-modal',
        '#confirmDeleteModal',
        '#productModal',
        '#productSummaryModal',
        '#adjustmentModal',
        '#detailAdjustmentModal',
        '#confirmAdjustmentModal',
        'div.fixed.inset-0[id*="modal" i]',
        'section.fixed.inset-0[id*="modal" i]',
        'div.fixed.inset-0[id*="confirm" i]',
        'section.fixed.inset-0[id*="confirm" i]'
    ].join(',');

    const INTERACTIVE_SELECTOR = [
        'input',
        'select',
        'textarea',
        'button',
        'a[href]',
        '[role="button"]',
        '[tabindex]:not([tabindex="-1"])',
        '[contenteditable="true"]',
        '.dropdown-list',
        '.dropdown-container'
    ].join(',');

    let cleanupTimer = 0;
    let lastCleanup = 0;

    function isElementHidden(element) {
        if (!element || !(element instanceof Element)) {
            return true;
        }

        if (element.classList.contains('hidden')) {
            return true;
        }

        if (element.getAttribute('aria-hidden') === 'true') {
            return true;
        }

        const style = window.getComputedStyle(element);
        if (style.display === 'none' || style.visibility === 'hidden') {
            return true;
        }

        const opacity = Number.parseFloat(style.opacity || '1');
        if (opacity <= 0.02) {
            return true;
        }

        const rect = element.getBoundingClientRect();
        return rect.width <= 0 || rect.height <= 0;
    }

    function isVisibleBlocker(element) {
        return !isElementHidden(element);
    }

    function cleanupNow() {
        cleanupTimer = 0;
        lastCleanup = Date.now();

        let visibleCount = 0;
        const blockers = document.querySelectorAll(BLOCKER_SELECTOR);

        blockers.forEach(function (element) {
            if (!(element instanceof HTMLElement)) {
                return;
            }

            const visible = isVisibleBlocker(element);
            if (visible) {
                visibleCount += 1;
                element.style.removeProperty('pointer-events');
                element.setAttribute('aria-hidden', 'false');
            } else {
                element.style.setProperty('pointer-events', 'none', 'important');
                element.setAttribute('aria-hidden', 'true');
            }
        });

        if (visibleCount === 0) {
            document.body.classList.remove('modal-open', 'overflow-hidden', 'tb-modal-lock', 'tb-has-visible-modal');
            document.documentElement.classList.remove('overflow-hidden');
            document.body.style.removeProperty('overflow');
        }
    }

    function scheduleCleanup(delay) {
        window.clearTimeout(cleanupTimer);
        cleanupTimer = window.setTimeout(cleanupNow, typeof delay === 'number' ? delay : 40);
    }

    function cleanupThrottled() {
        if (Date.now() - lastCleanup > 120) {
            cleanupNow();
            return;
        }
        scheduleCleanup(80);
    }

    function wrapFunction(name) {
        const original = window[name];
        if (typeof original !== 'function' || original.__tb39Wrapped === true) {
            return;
        }

        const wrapped = function () {
            const result = original.apply(this, arguments);
            scheduleCleanup(40);
            window.setTimeout(cleanupNow, 180);
            return result;
        };
        wrapped.__tb39Wrapped = true;
        window[name] = wrapped;
    }

    function wrapKnownModalFunctions() {
        [
            'openModal',
            'closeModal',
            'openPurchaseModal',
            'closePurchaseModal',
            'openPurchaseDetail',
            'closePurchaseDetail',
            'openPurchaseEdit',
            'showConfirmPurchaseModal',
            'hideConfirmPurchaseModal',
            'openPosModal',
            'closePosModal',
            'openProductSelectionModal',
            'closeProductSelectionModal',
            'showConfirmSubmitModal',
            'hideConfirmSubmitModal',
            'openCustomerModal',
            'openEditCustomerModal',
            'closeCustomerModal',
            'openDeleteConfirmation',
            'hideDeleteModal',
            'openCreateProductModal',
            'openShowProductModal',
            'openEditProductModal',
            'closeProductModal',
            'hideProductSummaryModal',
            'showConfirmAdjustmentModal',
            'hideConfirmAdjustmentModal',
            'openAdjustmentModal',
            'closeAdjustmentModal',
            'openDetailAdjustmentModal',
            'closeDetailAdjustmentModal'
        ].forEach(wrapFunction);
    }

    document.addEventListener('pointerdown', function (event) {
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        if (target.closest(INTERACTIVE_SELECTOR)) {
            cleanupThrottled();
            return;
        }

        const blocker = target.closest(BLOCKER_SELECTOR);
        if (blocker && isElementHidden(blocker)) {
            cleanupNow();
        }
    }, true);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            scheduleCleanup(20);
            window.setTimeout(cleanupNow, 120);
        }
    }, true);

    window.addEventListener('pageshow', function () {
        wrapKnownModalFunctions();
        scheduleCleanup(30);
    });

    window.addEventListener('focus', function () {
        scheduleCleanup(80);
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            wrapKnownModalFunctions();
            cleanupNow();
            window.setTimeout(function () {
                wrapKnownModalFunctions();
                cleanupNow();
            }, 250);
        }, { once: true });
    } else {
        wrapKnownModalFunctions();
        scheduleCleanup(30);
    }

    window.TB39Interaction = {
        cleanup: cleanupNow,
        refresh: cleanupNow,
        wrap: wrapKnownModalFunctions
    };
})();
/* TB39_INTERACTION_STABILIZER_END */

/* TB39_GLOBAL_UI_STABILITY_START
 * Runtime global untuk NativePHP desktop.
 * Tujuan: input, select, dropdown, tombol, dan modal tetap responsif.
 * Prinsip: jangan menanam pointer-events:none inline ke modal hidden.
 */
(function () {
    'use strict';

    if (window.__TB39_GLOBAL_UI_STABILITY__ === true) return;
    window.__TB39_GLOBAL_UI_STABILITY__ = true;

    const MODAL_SELECTOR = [
        '[role="dialog"]',
        'div.fixed.inset-0[id*="modal" i]',
        'section.fixed.inset-0[id*="modal" i]',
        'div.fixed.inset-0[id*="confirm" i]',
        'section.fixed.inset-0[id*="confirm" i]',
        '#purchaseModal',
        '#purchaseDetailModal',
        '#customer-modal',
        '#posModal',
        '#productSelectionModal'
    ].join(',');

    const FUNCTION_NAMES = [
        'openModal', 'closeModal', 'toggleModal',
        'openCustomerModal', 'openEditCustomerModal', 'closeCustomerModal', 'openDeleteConfirmation', 'hideDeleteModal',
        'openPurchaseModal', 'closePurchaseModal', 'openPurchaseEdit', 'openPurchaseDetail', 'closePurchaseDetail',
        'showConfirmPurchaseModal', 'hideConfirmPurchaseModal',
        'openPosModal', 'closePosModal', 'openProductSelectionModal', 'closeProductSelectionModal',
        'showConfirmSubmitModal', 'hideConfirmSubmitModal',
        'openAdjustmentModal', 'closeAdjustmentModal', 'showAdjustmentModalElement',
        'showConfirmAdjustmentModal', 'hideConfirmAdjustmentModal', 'hideDetailAdjustmentModal',
        'openReceivableModal', 'closeReceivableModal', 'showPaymentConfirmModal', 'hidePaymentConfirmModal',
        'openCreateSupplierModal', 'openEditSupplierModal', 'openCreateSalesModal', 'openEditSalesModal',
        'openPricingModal', 'closePricingModal', 'showConfirmSaveModal', 'hideConfirmSaveModal',
        'openCreateDeliveryModal', 'openStatusModal'
    ];

    let rafId = 0;
    let wrapTimer = 0;

    function isVisible(element) {
        if (!element || element.classList.contains('hidden')) return false;
        const style = window.getComputedStyle(element);
        if (style.display === 'none' || style.visibility === 'hidden') return false;
        const opacity = Number.parseFloat(style.opacity || '1');
        if (Number.isFinite(opacity) && opacity <= 0.03) return false;
        const rect = element.getBoundingClientRect();
        return rect.width > 0 && rect.height > 0;
    }

    function getModalRoots() {
        return Array.from(document.querySelectorAll(MODAL_SELECTOR));
    }

    function unlockControls(root) {
        if (!root) return;
        root.querySelectorAll('input, select, textarea, button, a, [tabindex]').forEach(function (control) {
            if (control.hasAttribute('disabled')) return;
            if (control.style && control.style.pointerEvents === 'none') {
                control.style.removeProperty('pointer-events');
            }
            control.removeAttribute('inert');
        });
    }

    function refreshUiState() {
        rafId = 0;
        let visibleModalCount = 0;

        getModalRoots().forEach(function (modal) {
            // Hapus sisa inline style dari patch lama. Class hidden/opacity yang mengatur state tetap dipertahankan.
            if (modal.style && modal.style.pointerEvents === 'none') {
                modal.style.removeProperty('pointer-events');
            }

            if (isVisible(modal)) {
                visibleModalCount += 1;
                modal.removeAttribute('inert');
                if (modal.getAttribute('aria-hidden') !== 'false') {
                    modal.setAttribute('aria-hidden', 'false');
                }
                unlockControls(modal);
            } else if (!modal.classList.contains('hidden') && modal.classList.contains('opacity-0')) {
                if (modal.getAttribute('aria-hidden') !== 'true') {
                    modal.setAttribute('aria-hidden', 'true');
                }
            }
        });

        if (visibleModalCount > 0) {
            document.body.classList.add('tb-has-visible-modal');
        } else {
            document.body.classList.remove('tb-has-visible-modal', 'tb-modal-lock');
            document.body.classList.remove('overflow-hidden');
            document.documentElement.classList.remove('overflow-hidden');
            if (document.body.style.overflow === 'hidden') {
                document.body.style.overflow = '';
            }
        }
    }

    function scheduleRefresh() {
        if (rafId) return;
        rafId = window.requestAnimationFrame(refreshUiState);
    }

    function wrapGlobalFunctions() {
        FUNCTION_NAMES.forEach(function (name) {
            const original = window[name];
            if (typeof original !== 'function' || original.__tb39UiStableWrapped === true) return;

            const wrapped = function () {
                scheduleRefresh();
                const result = original.apply(this, arguments);
                scheduleRefresh();
                window.setTimeout(scheduleRefresh, 40);
                window.setTimeout(scheduleRefresh, 180);
                return result;
            };

            wrapped.__tb39UiStableWrapped = true;
            window[name] = wrapped;
        });
    }

    function scheduleWrap() {
        window.clearTimeout(wrapTimer);
        wrapTimer = window.setTimeout(function () {
            wrapGlobalFunctions();
            scheduleRefresh();
        }, 30);
    }

    document.addEventListener('pointerdown', scheduleRefresh, true);
    document.addEventListener('click', scheduleRefresh, true);
    document.addEventListener('focusin', scheduleRefresh, true);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') window.setTimeout(scheduleRefresh, 30);
    }, true);

    window.addEventListener('pageshow', scheduleRefresh);
    window.addEventListener('focus', scheduleRefresh);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            scheduleWrap();
            scheduleRefresh();
        }, { once: true });
    } else {
        scheduleWrap();
        scheduleRefresh();
    }

    window.addEventListener('load', function () {
        scheduleWrap();
        scheduleRefresh();
    }, { once: true });

    // Observer ringan: hanya menjadwalkan 1 refresh per frame saat class/style modal berubah.
    try {
        const observer = new MutationObserver(function () {
            scheduleRefresh();
        });
        observer.observe(document.documentElement, {
            subtree: true,
            childList: true,
            attributes: true,
            attributeFilter: ['class', 'style', 'aria-hidden']
        });
        window.__TB39_GLOBAL_UI_OBSERVER__ = observer;
    } catch (error) {
        // Browser lama tetap jalan memakai event click/focus.
    }

    window.TB39UiStability = {
        refresh: refreshUiState,
        schedule: scheduleRefresh,
        wrap: wrapGlobalFunctions,
        visibleModals: function () { return getModalRoots().filter(isVisible); }
    };
})();
/* TB39_GLOBAL_UI_STABILITY_END */

/* TB39_CONFIRM_MODAL_CLEAN_JS_START
 * Stabilizer ringan untuk modal konfirmasi.
 * Memastikan modal confirm yang dibuka selalu terlihat, center, dan tombolnya bisa diklik.
 */
(function () {
    'use strict';

    if (window.__TB39_CONFIRM_MODAL_CLEAN__ === true) return;
    window.__TB39_CONFIRM_MODAL_CLEAN__ = true;

    const CONFIRM_SELECTOR = [
        '#confirmDeleteModal',
        '#confirmSubmitModal',
        '#confirmPurchaseModal',
        '#confirmSaveModal',
        '#confirmAdjustmentModal',
        '#confirm-payment-modal',
        '[id*="confirm" i].fixed.inset-0'
    ].join(',');

    const CONFIRM_FUNCTIONS = [
        'openDeleteConfirmation', 'hideDeleteModal',
        'showConfirmSubmitModal', 'hideConfirmSubmitModal',
        'showConfirmPurchaseModal', 'hideConfirmPurchaseModal',
        'showConfirmSaveModal', 'hideConfirmModal', 'hideConfirmSaveModal',
        'showConfirmAdjustmentModal', 'hideConfirmAdjustmentModal',
        'showPaymentConfirmModal', 'hidePaymentConfirmModal',
        'openSubmitConfirmation', 'closeSubmitConfirmation', 'executePendingFormSubmit'
    ];

    let rafId = 0;
    let wrapTimer = 0;

    function isHidden(modal) {
        if (!modal) return true;
        if (modal.classList.contains('hidden')) return true;
        const style = window.getComputedStyle(modal);
        return style.display === 'none' || style.visibility === 'hidden';
    }

    function normalize(modal) {
        if (!modal) return;

        if (isHidden(modal)) {
            modal.setAttribute('aria-hidden', 'true');
            return;
        }

        modal.classList.add('items-center', 'justify-center');
        if (!modal.classList.contains('flex')) {
            modal.classList.add('flex');
        }
        modal.classList.remove('opacity-0', 'pointer-events-none');
        modal.style.removeProperty('pointer-events');
        modal.style.removeProperty('visibility');
        modal.style.removeProperty('opacity');
        modal.removeAttribute('inert');
        modal.setAttribute('aria-hidden', 'false');
        modal.setAttribute('role', modal.getAttribute('role') || 'dialog');
        modal.setAttribute('aria-modal', modal.getAttribute('aria-modal') || 'true');

        Array.from(modal.children).forEach(function (child) {
            child.style.removeProperty('pointer-events');
            child.removeAttribute('inert');
        });

        modal.querySelectorAll('button, a, input, select, textarea, [tabindex]').forEach(function (control) {
            if (!control.hasAttribute('disabled')) {
                control.style.removeProperty('pointer-events');
                control.removeAttribute('inert');
            }
        });
    }

    function refresh() {
        rafId = 0;
        document.querySelectorAll(CONFIRM_SELECTOR).forEach(normalize);
    }

    function schedule(delay) {
        if (delay) {
            window.setTimeout(schedule, delay);
            return;
        }
        if (rafId) return;
        rafId = window.requestAnimationFrame(refresh);
    }

    function wrapFunctions() {
        CONFIRM_FUNCTIONS.forEach(function (name) {
            const original = window[name];
            if (typeof original !== 'function' || original.__tb39ConfirmCleanWrapped === true) return;
            const wrapped = function () {
                const result = original.apply(this, arguments);
                schedule();
                schedule(40);
                schedule(160);
                return result;
            };
            wrapped.__tb39ConfirmCleanWrapped = true;
            window[name] = wrapped;
        });
    }

    function scheduleWrap() {
        window.clearTimeout(wrapTimer);
        wrapTimer = window.setTimeout(function () {
            wrapFunctions();
            schedule();
        }, 50);
    }

    document.addEventListener('click', schedule, true);
    document.addEventListener('focusin', schedule, true);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') schedule(30);
    }, true);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            scheduleWrap();
            schedule();
        }, { once: true });
    } else {
        scheduleWrap();
        schedule();
    }

    window.addEventListener('load', function () {
        scheduleWrap();
        schedule();
    }, { once: true });

    window.TB39ConfirmModalClean = {
        refresh: refresh,
        schedule: schedule,
        wrap: wrapFunctions
    };
})();
/* TB39_CONFIRM_MODAL_CLEAN_JS_END */