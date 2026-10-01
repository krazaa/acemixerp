<?php

use Modules\Sales\Data\QuotationLineData;

test('calculates WHT on the subtotal plus GST', function () {
    $line = new QuotationLineData(
        itemId: 1,
        unitId: null,
        quantity: '100000.0000',
        unitPrice: '10.0000',
        taxRate: '18.000000',
        whtTaxRate: '2.300000',
    );

    expect($line->lineWhtTax())->toBe('27140.0000');
});
