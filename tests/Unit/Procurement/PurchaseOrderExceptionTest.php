<?php

use Modules\Procurement\Exceptions\PurchaseOrderException;
use Modules\Procurement\Models\PurchaseOrder;

test('rejects self approval with a clear message', function () {
    $purchaseOrder = new PurchaseOrder(['number' => 'PO-2026-000004']);

    $exception = PurchaseOrderException::cannotSelfApprove($purchaseOrder);

    expect($exception->getMessage())
        ->toBe('You submitted purchase order PO-2026-000004, so you cannot approve it. Ask another approver to review it.');
});
