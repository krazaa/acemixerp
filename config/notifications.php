<?php

use App\Models\JournalEntry;
use Modules\Expense\Models\ExpenseClaim;
use Modules\Inventory\Models\StockAdjustment;
use Modules\Inventory\Models\StockCount;
use Modules\Inventory\Models\StockTransfer;
use Modules\Manufacturing\Models\ProductionOrder;
use Modules\Procurement\Models\PurchaseOrder;
use Modules\Procurement\Models\PurchaseRequisition;
use Modules\Procurement\Models\SupplierInvoice;
use Modules\Procurement\Models\VendorInvoice;
use Modules\Procurement\Models\VendorPayment;
use Modules\Sales\Models\CustomerReceipt;
use Modules\Sales\Models\SalesInvoice;
use Modules\Sales\Models\SalesOrder;

return [
    'workflows' => [
        VendorInvoice::class => ['route' => 'expense.vendor-invoices.show', 'label' => 'Vendor invoice', 'approvals' => ['submitted' => 'approve', 'approved' => 'ownerApprove']],
        JournalEntry::class => ['route' => 'journals.show', 'label' => 'Journal', 'approvals' => ['submitted' => 'approve']],
        PurchaseRequisition::class => ['route' => 'procurement.purchase-requisitions.show', 'label' => 'Purchase requisition', 'approvals' => ['submitted' => 'approve', 'under_review' => 'approve']],
        PurchaseOrder::class => ['route' => 'procurement.purchase-orders.show', 'label' => 'Purchase order', 'approvals' => ['submitted' => 'approve']],
        SupplierInvoice::class => ['route' => 'procurement.supplier-invoices.show', 'label' => 'Supplier invoice', 'approvals' => ['matched' => 'approve', 'mismatch' => 'approve']],
        VendorPayment::class => ['route' => 'procurement.vendor-payments.show', 'label' => 'Vendor payment', 'approvals' => ['submitted' => 'approve']],
        StockAdjustment::class => ['route' => 'inventory.adjustments.show', 'label' => 'Stock adjustment', 'approvals' => ['submitted' => 'approve']],
        StockCount::class => ['route' => 'inventory.counts.show', 'label' => 'Stock count', 'approvals' => ['review' => 'approve']],
        StockTransfer::class => ['route' => 'inventory.transfers.show', 'label' => 'Stock transfer', 'approvals' => ['submitted' => 'approve']],
        SalesOrder::class => ['route' => 'sales.sales-orders.show', 'label' => 'Sales order', 'approvals' => ['submitted' => 'approve']],
        SalesInvoice::class => ['route' => 'sales.sales-invoices.show', 'label' => 'Sales invoice', 'approvals' => ['matched' => 'approve', 'mismatch' => 'approve']],
        CustomerReceipt::class => ['route' => 'sales.customer-receipts.show', 'label' => 'Customer receipt', 'approvals' => ['submitted' => 'approve']],
        ExpenseClaim::class => ['route' => 'expense.show', 'label' => 'Expense claim', 'approvals' => ['submitted' => 'managerApprove', 'manager_approved' => 'ceoApprove']],
        ProductionOrder::class => ['route' => 'manufacturing.production-orders.show', 'label' => 'Production order', 'approvals' => ['planned' => 'release']],
    ],
];
