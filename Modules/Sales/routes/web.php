<?php

use Illuminate\Support\Facades\Route;
use Modules\Sales\Http\Controllers\CustomerReceiptController;
use Modules\Sales\Http\Controllers\DeliveryController;
use Modules\Sales\Http\Controllers\QuotationController;
use Modules\Sales\Http\Controllers\SalesInvoiceController;
use Modules\Sales\Http\Controllers\SalesOrderController;
use Modules\Sales\Http\Controllers\SalesReturnController;

Route::middleware(['auth', 'verified'])
    ->prefix('sales')
    ->name('sales.')
    ->group(function () {
        Route::get('returns', [SalesReturnController::class, 'index'])->name('returns.index');
        Route::get('returns/create', [SalesReturnController::class, 'create'])->name('returns.create');
        Route::post('returns', [SalesReturnController::class, 'store'])->name('returns.store');
        Route::get('returns/{salesReturn}', [SalesReturnController::class, 'show'])->name('returns.show');
        Route::patch('returns/{salesReturn}/{step}', [SalesReturnController::class, 'transition'])->whereIn('step', ['approve', 'reject', 'receive', 'inspect', 'accept', 'credit', 'post'])->name('returns.transition');
        // ── Quotations ─────────────────────────────────────────────
        Route::resource('quotations', QuotationController::class)
            ->parameters(['quotations' => 'quotation']);

        Route::prefix('quotations/{quotation}')->name('quotations.')->group(function () {
            Route::patch('send', [QuotationController::class, 'send'])->name('send');
            Route::patch('accept', [QuotationController::class, 'accept'])->name('accept');
            Route::patch('reject', [QuotationController::class, 'reject'])->name('reject');
            Route::patch('cancel', [QuotationController::class, 'cancel'])->name('cancel');
            Route::post('convert', [QuotationController::class, 'convert'])->name('convert');
        });

        // ── Sales Orders ───────────────────────────────────────────
        Route::resource('sales-orders', SalesOrderController::class)
            ->parameters(['sales-orders' => 'salesOrder']);

        Route::prefix('sales-orders/{salesOrder}')->name('sales-orders.')->group(function () {
            Route::patch('submit', [SalesOrderController::class, 'submit'])->name('submit');
            Route::patch('approve', [SalesOrderController::class, 'approve'])->name('approve');
            Route::patch('reject', [SalesOrderController::class, 'reject'])->name('reject');
            Route::patch('release-hold', [SalesOrderController::class, 'releaseHold'])->name('release-hold');
            Route::patch('confirm', [SalesOrderController::class, 'confirm'])->name('confirm');
            Route::patch('cancel', [SalesOrderController::class, 'cancel'])->name('cancel');
            Route::patch('close', [SalesOrderController::class, 'close'])->name('close');
        });

        Route::resource('deliveries', DeliveryController::class)
            ->parameters(['deliveries' => 'delivery']);

        Route::prefix('deliveries/{delivery}')->name('deliveries.')->group(function () {
            Route::patch('pick', [DeliveryController::class, 'pick'])->name('pick');
            Route::patch('dispatch', [DeliveryController::class, 'dispatch'])->name('dispatch');
            Route::patch('mark-delivered', [DeliveryController::class, 'markDelivered'])->name('mark-delivered');
            Route::patch('close', [DeliveryController::class, 'close'])->name('close');
            Route::patch('cancel', [DeliveryController::class, 'cancel'])->name('cancel');
        });

        Route::get('deliveries/{delivery}/note', [DeliveryController::class, 'printNote'])
            ->name('deliveries.note');

        // Invoices
        Route::resource('sales-invoices', SalesInvoiceController::class)
            ->parameters(['sales-invoices' => 'salesInvoice']);

        Route::prefix('sales-invoices/{salesInvoice}')->name('sales-invoices.')->group(function () {
            Route::get('print', [SalesInvoiceController::class, 'print'])->name('print');
            Route::get('export/csv', [SalesInvoiceController::class, 'csv'])->name('csv');
            Route::patch('match', [SalesInvoiceController::class, 'match'])->name('match');
            Route::patch('approve', [SalesInvoiceController::class, 'approve'])->name('approve');
            Route::patch('reject', [SalesInvoiceController::class, 'reject'])->name('reject');
            Route::patch('post', [SalesInvoiceController::class, 'post'])->name('post');
            Route::patch('cancel', [SalesInvoiceController::class, 'cancel'])->name('cancel');
            Route::patch('reverse', [SalesInvoiceController::class, 'reverse'])->name('reverse');
        });

        // Receipts
        Route::resource('customer-receipts', CustomerReceiptController::class)
            ->parameters(['customer-receipts' => 'customerReceipt']);

        Route::prefix('customer-receipts/{customerReceipt}')->name('customer-receipts.')->group(function () {
            Route::patch('submit', [CustomerReceiptController::class, 'submit'])->name('submit');
            Route::patch('approve', [CustomerReceiptController::class, 'approve'])->name('approve');
            Route::patch('reject', [CustomerReceiptController::class, 'reject'])->name('reject');
            Route::patch('post', [CustomerReceiptController::class, 'post'])->name('post');
            Route::patch('cancel', [CustomerReceiptController::class, 'cancel'])->name('cancel');
            Route::patch('reverse', [CustomerReceiptController::class, 'reverse'])->name('reverse');
        });

        Route::get('customers/{customer}/outstanding-invoices',
            [CustomerReceiptController::class, 'outstandingInvoices'])
            ->name('customer-receipts.outstanding-invoices');

    });
