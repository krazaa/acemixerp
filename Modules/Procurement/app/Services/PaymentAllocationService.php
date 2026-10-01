<?php

namespace Modules\Procurement\Services;

use Illuminate\Support\Facades\DB;
use Modules\Procurement\Data\PaymentAllocationData;
use Modules\Procurement\Enums\SupplierInvoiceStatus;
use Modules\Procurement\Exceptions\VendorPaymentException;
use Modules\Procurement\Models\SupplierInvoice;
use Modules\Procurement\Models\VendorPayment;

final class PaymentAllocationService
{
    /**
     * Allocate a posted vendor payment to one or more supplier invoices.
     * Runs inside an existing transaction — callers are responsible for the outer DB::transaction.
     *
     * @param  PaymentAllocationData[]  $allocations
     */
    public function allocate(VendorPayment $payment, array $allocations, int $userId): void
    {
        $allocated = (string) $payment->allocated_amount;

        foreach ($allocations as $alloc) {
            /** @var SupplierInvoice $invoice */
            $invoice = SupplierInvoice::query()
                ->lockForUpdate()
                ->findOrFail($alloc->supplierInvoiceId);

            if ($invoice->vendor_id !== $payment->vendor_id) {
                throw VendorPaymentException::vendorMismatch($payment, $invoice);
            }

            if (! in_array($invoice->status, [
                SupplierInvoiceStatus::Posted,
                SupplierInvoiceStatus::PartiallyPaid,
            ], true)) {
                throw VendorPaymentException::invoiceNotPayable($invoice);
            }

            $outstanding = $invoice->outstanding();

            if (bccomp($alloc->amount, $outstanding, 4) > 0) {
                throw VendorPaymentException::overAllocation($invoice, $alloc->amount, $outstanding);
            }

            // Record the allocation
            $payment->allocations()->create([
                'payment_type' => VendorPayment::class,
                'payment_id' => $payment->id,
                'allocatable_type' => SupplierInvoice::class,
                'allocatable_id' => $invoice->id,
                'amount' => $alloc->amount,
                'allocated_by' => $userId,
            ]);

            // Update the invoice paid_amount and status
            $newPaid = bcadd((string) $invoice->paid_amount, $alloc->amount, 4);

            $invoice->forceFill([
                'paid_amount' => $newPaid,
                'status' => bccomp($newPaid, (string) $invoice->total, 4) >= 0
                    ? SupplierInvoiceStatus::Paid
                    : SupplierInvoiceStatus::PartiallyPaid,
            ])->save();

            $allocated = bcadd($allocated, $alloc->amount, 4);
        }

        if (bccomp($allocated, (string) $payment->amount, 4) > 0) {
            throw VendorPaymentException::paymentOverAllocated($payment, $allocated);
        }

        $payment->forceFill(['allocated_amount' => $allocated])->save();
    }

    /**
     * Reverse the allocations of a payment (used when reversing a payment).
     * Each affected invoice has paid_amount reduced and status reset to posted.
     */
    public function deallocate(VendorPayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            foreach ($payment->allocations()->with('allocatable')->get() as $allocation) {
                $invoice = $allocation->allocatable;
                if (! $invoice instanceof SupplierInvoice) {
                    continue;
                }

                $invoice->forceFill([
                    'paid_amount' => bcsub((string) $invoice->paid_amount, (string) $allocation->amount, 4),
                    'status' => SupplierInvoiceStatus::Posted,
                ])->save();

                $allocation->delete();
            }

            $payment->forceFill(['allocated_amount' => '0.0000'])->save();
        });
    }
}
