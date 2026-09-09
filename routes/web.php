<?php

use App\Http\Controllers\AdjustmentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DataTagihanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryReportController;
use App\Http\Controllers\MasterManagementController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReceivablesController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/', [LoginController::class, 'showLoginForm']);
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/customer', [CustomerController::class, 'index'])->name('customer.index');
    Route::post('/customer', [CustomerController::class, 'store'])->name('customer.store');
    Route::get('/customer/{id}', [CustomerController::class, 'show'])->name('customer.show');
    Route::put('/customer/{id}', [CustomerController::class, 'update'])->name('customer.update');
    Route::delete('/customer/{id}', [CustomerController::class, 'destroy'])->name('customer.destroy');

    Route::get('/product', [ProductController::class, 'index'])->name('product.index');
    Route::post('/product', [ProductController::class, 'store'])->name('product.store');
    Route::post('/product/import', [ProductController::class, 'import'])->name('product.import');
    Route::get('/product/{id}', [ProductController::class, 'show'])->whereNumber('id')->name('product.show');
    Route::put('/product/{id}', [ProductController::class, 'update'])->whereNumber('id')->name('product.update');
    Route::delete('/product/{id}', [ProductController::class, 'destroy'])->whereNumber('id')->name('product.destroy');

    Route::get('/master-management', [MasterManagementController::class, 'index'])->name('master-management.index');
    Route::get('/master-management/supplier/{id}', [MasterManagementController::class, 'showSupplier'])->whereNumber('id')->name('master-management.supplier.show');
    Route::post('/master-management/supplier', [MasterManagementController::class, 'storeSupplier'])->name('master-management.supplier.store');
    Route::put('/master-management/supplier/{id}', [MasterManagementController::class, 'updateSupplier'])->whereNumber('id')->name('master-management.supplier.update');
    Route::delete('/master-management/supplier/{id}', [MasterManagementController::class, 'destroySupplier'])->whereNumber('id')->name('master-management.supplier.destroy');
    Route::get('/master-management/sales/{id}', [MasterManagementController::class, 'showSales'])->whereNumber('id')->name('master-management.sales.show');
    Route::post('/master-management/sales', [MasterManagementController::class, 'storeSales'])->name('master-management.sales.store');
    Route::put('/master-management/sales/{id}', [MasterManagementController::class, 'updateSales'])->whereNumber('id')->name('master-management.sales.update');
    Route::delete('/master-management/sales/{id}', [MasterManagementController::class, 'destroySales'])->whereNumber('id')->name('master-management.sales.destroy');

    Route::get('/pricing', [\App\Http\Controllers\PricingController::class, 'index'])->name('pricing.index');
    Route::get('/pricing/data', [\App\Http\Controllers\PricingController::class, 'filterData'])->name('pricing.data');
    Route::get('/pricing/modal-products', [\App\Http\Controllers\PricingController::class, 'getModalProducts'])->name('pricing.modal_products');
    Route::post('/pricing/save', [\App\Http\Controllers\PricingController::class, 'store'])->name('pricing.store');
    Route::get('/pricing/import-template', [\App\Http\Controllers\PricingController::class, 'downloadImportTemplate'])->name('pricing.import_template');
    Route::post('/pricing/import-preview', [\App\Http\Controllers\PricingController::class, 'importPreview'])->name('pricing.import_preview');
    Route::post('/pricing/import-execute', [\App\Http\Controllers\PricingController::class, 'importExecute'])->name('pricing.import_execute');


    Route::get('/sales', [SalesController::class, 'index'])->name('sales.index');
    Route::get('/sales/search-products', [SalesController::class, 'searchProducts'])->name('sales.search_products');
    Route::get('/sales/get-price', [SalesController::class, 'getProductPrice'])->name('sales.get_price');
    Route::post('/sales/store', [SalesController::class, 'store'])->name('sales.store');
    Route::get('/sales/{id}/print', [SalesController::class, 'print'])->whereNumber('id')->name('sales.print');
    Route::post('/sales/{id}/print-direct', [SalesController::class, 'printReceiptDirect'])->whereNumber('id')->name('sales.print_direct');
    Route::get('/sales/{id}/invoice', [SalesController::class, 'invoice'])->whereNumber('id')->name('sales.invoice');
    Route::post('/sales/{id}/invoice-direct', [SalesController::class, 'printInvoiceDirect'])->whereNumber('id')->name('sales.invoice_direct');
    Route::put('/sales/{id}', [SalesController::class, 'update'])->whereNumber('id')->name('sales.update');
    Route::post('/sales/{id}/refund', [SalesController::class, 'refund'])->whereNumber('id')->name('sales.refund');
    Route::get('/sales/refunds/{refund}/print', [SalesController::class, 'printRefund'])->whereNumber('refund')->name('sales.refund.print');
    Route::get('/sales/{id}', [SalesController::class, 'show'])->whereNumber('id')->name('sales.show');
	Route::get('/sales/{id}/print-escp', [SalesController::class, 'printInvoiceEscp']);
    Route::get('/sales/{id}/test', function ($id) {
        return redirect('/sales/' . $id . '/print-escp?type=khusus&preview=1');
    })->whereNumber('id');

    Route::get('/purchase', [PurchaseController::class, 'index'])->name('purchase.index');
    Route::get('/purchase/export-excel', [PurchaseController::class, 'exportExcel'])->name('purchase.export_excel');
    Route::post('/purchase/store', [PurchaseController::class, 'store'])->name('purchase.store');
    Route::put('/purchase/{id}', [PurchaseController::class, 'update'])->whereNumber('id')->name('purchase.update');
    Route::post('/purchase/{id}/payments', [PurchaseController::class, 'storePayment'])->whereNumber('id')->name('purchase.payments.store');
    Route::get('/purchase/{id}/invoice', [PurchaseController::class, 'invoice'])->whereNumber('id')->name('purchase.invoice');
    Route::get('/purchase/{id}', [PurchaseController::class, 'show'])->whereNumber('id')->name('purchase.show');

    Route::get('/adjustment', [AdjustmentController::class, 'index'])->name('adjustment.index');
    Route::post('/adjustment/draft', [AdjustmentController::class, 'storeDraft'])->name('adjustment.draft');
    Route::post('/adjustment/sync-all', [AdjustmentController::class, 'syncAllStock'])->name('adjustment.sync_all');
    Route::post('/adjustment/sync/{id}', [AdjustmentController::class, 'syncStock'])->whereNumber('id')->name('adjustment.sync');

    Route::get('/receivables', [ReceivablesController::class, 'index'])->name('receivables.index');
    Route::get('/receivables/export-excel', [ReceivablesController::class, 'exportExcel'])->name('receivables.export_excel');
    Route::get('/receivables/export-pdf', [ReceivablesController::class, 'exportPdf'])->name('receivables.export_pdf');
    Route::get('/receivables/{id}', [ReceivablesController::class, 'showPiutang'])->whereNumber('id')->name('receivables.show');
    Route::post('/receivables/pay', [ReceivablesController::class, 'storeCicilan'])->name('receivables.store_payment');

    Route::get('/data-tagihan', [DataTagihanController::class, 'index'])->name('data-tagihan.index');
    Route::get('/data-tagihan/create', [DataTagihanController::class, 'create'])->name('data-tagihan.create');
    Route::get('/data-tagihan/unpaid-invoices', [DataTagihanController::class, 'getUnpaidInvoices'])->name('data-tagihan.unpaid_invoices');
    Route::get('/data-tagihan/export-excel', [DataTagihanController::class, 'exportExcel'])->name('data-tagihan.export_excel');
    Route::post('/data-tagihan', [DataTagihanController::class, 'store'])->name('data-tagihan.store');
    Route::get('/data-tagihan/{id}', [DataTagihanController::class, 'show'])->whereNumber('id')->name('data-tagihan.show');
    Route::get('/data-tagihan/{id}/print', [DataTagihanController::class, 'print'])->whereNumber('id')->name('data-tagihan.print');
    Route::get('/data-tagihan/{id}/settle', [DataTagihanController::class, 'settle'])->whereNumber('id')->name('data-tagihan.settle');
    Route::post('/data-tagihan/{id}/settle', [DataTagihanController::class, 'processSettlement'])->whereNumber('id')->name('data-tagihan.process_settlement');
    Route::delete('/data-tagihan/{id}', [DataTagihanController::class, 'destroy'])->whereNumber('id')->name('data-tagihan.destroy');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export_pdf');
    Route::get('/reports/export-excel', [ReportController::class, 'exportExcel'])->name('reports.export_excel');

    Route::get('/inventory-report', [InventoryReportController::class, 'index'])->name('inventory_report.index');
    Route::get('/inventory-report/export-excel', [InventoryReportController::class, 'export_excel'])->name('inventory_report.export_excel');
    Route::get('/inventory-report/export-pdf', [InventoryReportController::class, 'export_pdf'])->name('inventory_report.export_pdf');
    Route::get('/inventory-report/{id}', [InventoryReportController::class, 'show'])->whereNumber('id')->name('inventory_report.show');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/printers', [SettingsController::class, 'savePrinterMapping'])->name('settings.save_printers');
    Route::post('/settings/data-directory', [SettingsController::class, 'chooseDataDirectory'])->name('settings.choose_directory');
    Route::post('/settings/test-printer', [SettingsController::class, 'testPrinter'])->name('settings.test_printer');
    Route::post('/settings/backup', [SettingsController::class, 'triggerBackup'])->name('settings.backup');
    Route::post('/settings/restore', [SettingsController::class, 'triggerRestore'])->name('settings.restore');
});
