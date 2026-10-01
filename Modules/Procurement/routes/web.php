<?php

use Illuminate\Support\Facades\Route;
use Modules\Procurement\Http\Controllers\GoodsReceiptController;
use Modules\Procurement\Http\Controllers\PurchaseOrderController;
use Modules\Procurement\Http\Controllers\PurchaseRequisitionController;
use Modules\Procurement\Http\Controllers\RequisitionRfqController;
use Modules\Procurement\Http\Controllers\RfqController;
use Modules\Procurement\Http\Controllers\SupplierInvoiceController;
use Modules\Procurement\Http\Controllers\VendorPaymentController;
use Modules\Procurement\Http\Controllers\VendorQuotationController;

Route::middleware(['auth', 'verified'])
    ->prefix('procurement')
    ->name('procurement.')->group(function () {

        Route::resource('purchase-requisitions', PurchaseRequisitionController::class)
            ->parameters(['purchase-requisitions' => 'purchaseRequisition']);

        Route::prefix('purchase-requisitions/{purchaseRequisition}')
            ->name('purchase-requisitions.')
            ->group(function () {
                Route::get('convert-to-rfq', [RequisitionRfqController::class, 'create'])->middleware('active')->name('convert-to-rfq');
                Route::post('convert-to-rfq', [RequisitionRfqController::class, 'store'])->middleware('active')->name('store-rfq');
                Route::patch('submit', [PurchaseRequisitionController::class, 'submit'])->name('submit');
                Route::patch('start-review', [PurchaseRequisitionController::class, 'startReview'])->name('start-review');
                Route::patch('approve', [PurchaseRequisitionController::class, 'approve'])->name('approve');
                Route::patch('reject', [PurchaseRequisitionController::class, 'reject'])->name('reject');
                Route::patch('cancel', [PurchaseRequisitionController::class, 'cancel'])->name('cancel');
                Route::patch('close', [PurchaseRequisitionController::class, 'close'])->name('close');
            });

        // RFQs
        Route::resource('rfqs', RfqController::class);
        Route::get('rfqs/{rfq}/print', [RfqController::class, 'print'])->name('rfqs.print');
        Route::get('rfqs/{rfq}/print-request/{vendor?}', [RfqController::class, 'printRequest'])->name('rfqs.print-request');
        Route::prefix('rfqs/{rfq}')->name('rfqs.')->group(function () {
            Route::patch('issue', [RfqController::class, 'issue'])->name('issue');
            Route::patch('cancel', [RfqController::class, 'cancel'])->name('cancel');
            Route::patch('close', [RfqController::class, 'close'])->name('close');
            Route::patch('award-lines', [VendorQuotationController::class, 'awardLines'])->middleware('active')->name('award-lines');
            Route::get('compare', [RfqController::class, 'compare'])->name('compare');

        });
        Route::post('rfqs/{rfq}/create-po', [RfqController::class, 'createPo'])->middleware('active')->name('rfqs.create-po');
        // Quotations
        Route::scopeBindings()->prefix('rfqs/{rfq}/quotations')->name('quotations.')->group(function () {
            Route::get('create', [VendorQuotationController::class, 'create'])->name('create');
            Route::post('', [VendorQuotationController::class, 'store'])->name('store');
            Route::patch('{quotation}/award', [VendorQuotationController::class, 'award'])->middleware('active')->name('award');
        });

        Route::prefix('quotations/{quotation}')->name('quotations.')->group(function () {
            Route::patch('submit', [VendorQuotationController::class, 'submit'])->name('submit');
        });

        // Purchase Orders
        Route::resource('purchase-orders', PurchaseOrderController::class)
            ->parameters(['purchase-orders' => 'purchaseOrder']);

        Route::prefix('purchase-orders/{purchaseOrder}')->name('purchase-orders.')->group(function () {
            Route::patch('submit', [PurchaseOrderController::class, 'submit'])->name('submit');
            Route::patch('approve', [PurchaseOrderController::class, 'approve'])->name('approve');
            Route::patch('reject', [PurchaseOrderController::class, 'reject'])->name('reject');
            Route::patch('issue', [PurchaseOrderController::class, 'issue'])->name('issue');
            Route::patch('cancel', [PurchaseOrderController::class, 'cancel'])->name('cancel');
            Route::patch('close', [PurchaseOrderController::class, 'close'])->name('close');
        });

        Route::get('/purchase-orders/{purchaseOrder}/print', [PurchaseOrderController::class, 'print'])
            ->name('purchase-orders.print');

        // Goods Receipts
        Route::resource('goods-receipts', GoodsReceiptController::class)
            ->parameters(['goods-receipts' => 'goodsReceipt']);

        Route::prefix('goods-receipts/{goodsReceipt}')->name('goods-receipts.')->group(function () {
            Route::patch('post', [GoodsReceiptController::class, 'post'])->name('post');
            Route::patch('cancel', [GoodsReceiptController::class, 'cancel'])->name('cancel');
        });

        Route::get('/goods-receipts/{goodsReceipt}/print', [GoodsReceiptController::class, 'print'])->name('goods-receipts.print');

        Route::resource('supplier-invoices', SupplierInvoiceController::class)
            ->parameters(['supplier-invoices' => 'supplierInvoice']);

        Route::prefix('supplier-invoices/{supplierInvoice}')->name('supplier-invoices.')->group(function () {
            Route::patch('match', [SupplierInvoiceController::class, 'match'])->name('match');
            Route::patch('approve', [SupplierInvoiceController::class, 'approve'])->name('approve');
            Route::patch('reject', [SupplierInvoiceController::class, 'reject'])->name('reject');
            Route::patch('post', [SupplierInvoiceController::class, 'post'])->name('post');
            Route::patch('cancel', [SupplierInvoiceController::class, 'cancel'])->name('cancel');
        });

        // ── Vendor Payments ─────────────────────────────────────────────────
        Route::resource('vendor-payments', VendorPaymentController::class)
            ->parameters(['vendor-payments' => 'vendorPayment'])
            ->except(['destroy']);

        Route::prefix('vendor-payments/{vendorPayment}')
            ->name('vendor-payments.')
            ->group(function () {
                Route::get('voucher', [VendorPaymentController::class, 'voucher'])->name('voucher');
                Route::patch('submit', [VendorPaymentController::class, 'submit'])->name('submit');
                Route::patch('approve', [VendorPaymentController::class, 'approve'])->name('approve');
                Route::patch('reject', [VendorPaymentController::class, 'reject'])->name('reject');
                Route::patch('post', [VendorPaymentController::class, 'post'])->name('post');
                Route::patch('reverse', [VendorPaymentController::class, 'reverse'])->name('reverse');
                Route::patch('cancel', [VendorPaymentController::class, 'cancel'])->name('cancel');
                Route::delete('', [VendorPaymentController::class, 'destroy'])->name('destroy');
            });

        // Invoice picker for the "New Payment" form — returns the vendor's outstanding invoices as JSON.
        Route::get('vendor-payments/outstanding-invoices/{vendor}',
            [VendorPaymentController::class, 'outstandingInvoices'])
            ->name('vendor-payments.outstanding-invoices');

    });
