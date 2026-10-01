<?php

use Illuminate\Support\Facades\Route;
use Modules\Expense\Http\Controllers\ExpenseController;
use Modules\Expense\Http\Controllers\VendorInvoiceController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('expenses', [ExpenseController::class, 'index'])->name('expense.index');
    Route::get('expenses/vendor-invoices', [VendorInvoiceController::class, 'index'])->name('expense.vendor-invoices.index');
    Route::get('expenses/vendor-invoices/create', [VendorInvoiceController::class, 'create'])->name('expense.vendor-invoices.create');
    Route::post('expenses/vendor-invoices', [VendorInvoiceController::class, 'store'])->name('expense.vendor-invoices.store');
    Route::get('expenses/vendor-invoices/{vendorInvoice}/edit', [VendorInvoiceController::class, 'edit'])->name('expense.vendor-invoices.edit');
    Route::patch('expenses/vendor-invoices/{vendorInvoice}', [VendorInvoiceController::class, 'update'])->name('expense.vendor-invoices.update');
    Route::patch('expenses/vendor-invoices/{vendorInvoice}/submit', [VendorInvoiceController::class, 'submit'])->name('expense.vendor-invoices.submit');
    Route::patch('expenses/vendor-invoices/{vendorInvoice}/approve', [VendorInvoiceController::class, 'approve'])->name('expense.vendor-invoices.approve');
    Route::patch('expenses/vendor-invoices/{vendorInvoice}/owner-approve', [VendorInvoiceController::class, 'ownerApprove'])->name('expense.vendor-invoices.owner-approve');
    Route::patch('expenses/vendor-invoices/{vendorInvoice}/reject', [VendorInvoiceController::class, 'reject'])->name('expense.vendor-invoices.reject');
    Route::patch('expenses/vendor-invoices/{vendorInvoice}/post', [VendorInvoiceController::class, 'post'])->name('expense.vendor-invoices.post');
    Route::patch('expenses/vendor-invoices/{vendorInvoice}/pay', [VendorInvoiceController::class, 'pay'])->name('expense.vendor-invoices.pay');
    Route::get('expenses/vendor-invoices/{vendorInvoice}', [VendorInvoiceController::class, 'show'])->name('expense.vendor-invoices.show');
    Route::get('expenses/vendor-invoices/{vendorInvoice}/pdf', [VendorInvoiceController::class, 'pdf'])->name('expense.vendor-invoices.pdf');
    Route::get('expenses/create', [ExpenseController::class, 'create'])->name('expense.create');
    Route::post('expenses', [ExpenseController::class, 'store'])->name('expense.store');
    Route::get('expenses/{claim}', [ExpenseController::class, 'show'])->name('expense.show');
    Route::get('expenses/{claim}/pdf', [ExpenseController::class, 'pdf'])->name('expense.pdf');
    Route::get('expenses/{claim}/edit', [ExpenseController::class, 'edit'])->name('expense.edit');
    Route::patch('expenses/{claim}', [ExpenseController::class, 'update'])->name('expense.update');
    Route::get('expenses/{claim}/evidence/{evidence}', [ExpenseController::class, 'downloadEvidence'])->name('expense.evidence.download');
    Route::get('expenses/{claim}/evidence/{evidence}/preview', [ExpenseController::class, 'previewEvidence'])->name('expense.evidence.preview');
    Route::patch('expenses/{claim}/submit', [ExpenseController::class, 'submit'])->name('expense.submit');
    Route::patch('expenses/{claim}/manager-approve', [ExpenseController::class, 'managerApprove'])->name('expense.manager-approve');
    Route::patch('expenses/{claim}/ceo-approve', [ExpenseController::class, 'ceoApprove'])->name('expense.ceo-approve');
    Route::patch('expenses/{claim}/ceo-reject', [ExpenseController::class, 'ceoReject'])->name('expense.ceo-reject');
    Route::patch('expenses/{claim}/reimburse', [ExpenseController::class, 'reimburse'])->name('expense.reimburse');
});
