<?php

use Modules\Sales\Data\SalesOrderData;
use Modules\Sales\Data\SalesOrderLineData;

test('includes WHT in sales-order lines and totals', function () {
    $line = new SalesOrderLineData(
        itemId: 1,
        unitId: null,
        quantity: '100.0000',
        unitPrice: '10.0000',
        taxRate: '18.000000',
        whtTaxRate: '2.300000',
    );
    $order = new SalesOrderData(
        customerId: 1,
        orderDate: now(),
        currencyCode: 'PKR',
        lines: [$line],
    );

    expect($line->toArray()['wht_tax_rate'])->toBe('2.300000')
        ->and($line->lineWhtTax())->toBe('27.1400')
        ->and($line->lineTotal())->toBe('1152.8600')
        ->and($order->whtTaxTotal())->toBe('27.1400')
        ->and($order->total())->toBe('1152.8600');
});
